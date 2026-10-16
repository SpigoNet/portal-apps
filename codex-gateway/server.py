"""Gateway compatível com a API da OpenAI que delega ao CLI `codex` (login de conta, sem API key).

Endpoints: /v1/models, /v1/chat/completions (texto e visão), /v1/images/generations e /v1/images/edits.
"""
import asyncio
import base64
import io
import json
import os
import shutil
import tempfile
import time
import urllib.request
import uuid
from pathlib import Path
from typing import Any, AsyncIterator

from fastapi import Depends, FastAPI, Header, HTTPException, Request
from fastapi.responses import FileResponse, JSONResponse, StreamingResponse
from PIL import Image

GATEWAY_TOKEN = os.getenv("GATEWAY_TOKEN", "")  # opcional: protege este gateway (não é chave da OpenAI)
DEFAULT_MODEL = os.getenv("CODEX_DEFAULT_MODEL", "")  # vazio = padrão do CLI
TIMEOUT = int(os.getenv("CODEX_TIMEOUT", "300"))
IMAGE_TIMEOUT = int(os.getenv("CODEX_IMAGE_TIMEOUT", "600"))
MAX_CONCURRENCY = int(os.getenv("CODEX_MAX_CONCURRENCY", "2"))
MODELS = [m.strip() for m in os.getenv(
    "CODEX_MODELS", "gpt-6.1-sol,gpt-6-sol,gpt-6-astra,gpt-6-luna,gpt-5.6-sol,gpt-5.6-terra,gpt-5.6-luna"
).split(",") if m.strip()]
IMAGE_MODELS = [m.strip() for m in os.getenv("CODEX_IMAGE_MODELS", "gpt-image-1").split(",") if m.strip()]
CODEX_HOME = Path(os.getenv("CODEX_HOME", "/data/codex"))
PUBLIC_URL = os.getenv("GATEWAY_PUBLIC_URL", "").rstrip("/")  # base para response_format=url
FILES_DIR = Path(os.getenv("GATEWAY_FILES_DIR", "/tmp/gateway-files"))
FILES_TTL = int(os.getenv("GATEWAY_FILES_TTL", "3600"))
FORMATS = {"png": ("PNG", "image/png"), "jpeg": ("JPEG", "image/jpeg"), "webp": ("WEBP", "image/webp")}

app = FastAPI(title="Codex Gateway")
sem = asyncio.Semaphore(MAX_CONCURRENCY)
FILES_DIR.mkdir(parents=True, exist_ok=True)


def auth(authorization: str | None = Header(default=None)) -> None:
    if GATEWAY_TOKEN and authorization != f"Bearer {GATEWAY_TOKEN}":
        raise HTTPException(401, "Token inválido")


class CliError(Exception):
    def __init__(self, status: int, msg: str) -> None:
        super().__init__(msg)
        self.status, self.msg = status, msg


@app.exception_handler(CliError)
async def cli_error_handler(_: Request, e: CliError) -> JSONResponse:
    return err(e.status, e.msg)


def err(status: int, msg: str) -> JSONResponse:
    return JSONResponse({"error": {"message": msg, "type": "codex_cli_error"}}, status_code=status)


def child_env() -> dict[str, str]:
    env = dict(os.environ)
    for k in ("OPENAI_API_KEY", "CODEX_API_KEY"):
        env.pop(k, None)  # garante uso da conta logada
    return env


class Run:
    """Resultado de uma execução do `codex exec`."""

    def __init__(self) -> None:
        self.thread_id = ""
        self.text = ""
        self.usage: dict = {}
        self.error = ""


def cli_args(model: str, effort: str | None, images: list[Path], image_gen: bool) -> list[str]:
    args = ["codex", "exec", "--json", "--ephemeral", "--skip-git-repo-check", "--ignore-rules",
            "-s", "read-only", "-c", 'web_search="disabled"']
    if not image_gen:
        args += ["--disable", "image_generation"]
    if model:
        args += ["-m", model]
    if effort in ("minimal", "low", "medium", "high", "xhigh"):
        args += ["-c", f'model_reasoning_effort="{effort}"']
    for p in images:
        args += ["-i", str(p)]
    return args  # o prompt vai pela stdin


async def spawn(args: list[str], prompt: str, cwd: Path) -> asyncio.subprocess.Process:
    proc = await asyncio.create_subprocess_exec(
        *args, stdin=asyncio.subprocess.PIPE, stdout=asyncio.subprocess.PIPE,
        stderr=asyncio.subprocess.PIPE, env=child_env(), cwd=cwd, limit=16 * 1024 * 1024)
    proc.stdin.write(prompt.encode())
    await proc.stdin.drain()
    proc.stdin.close()
    return proc


def parse_event(line: bytes, run: Run) -> None:
    try:
        ev = json.loads(line)
    except ValueError:
        return
    t = ev.get("type")
    if t == "thread.started":
        run.thread_id = ev.get("thread_id", "")
    elif t == "item.completed" and (ev.get("item") or {}).get("type") == "agent_message":
        run.text = ev["item"].get("text", "")
    elif t == "turn.completed":
        run.usage = ev.get("usage") or {}
    elif t == "turn.failed":  # eventos "error" avulsos são avisos/reconexões do CLI, não falhas
        msg = (ev.get("error") or {}).get("message") or "Falha no CLI"
        try:  # a mensagem costuma ser um JSON da API
            msg = json.loads(msg).get("error", {}).get("message", msg)
        except (ValueError, AttributeError):
            pass
        run.error = str(msg)


async def run_codex(args: list[str], prompt: str, timeout: int) -> Run:
    """Executa o CLI numa pasta de trabalho vazia e descartável."""
    run = Run()
    cwd = Path(tempfile.mkdtemp(prefix="cx-"))
    proc = await spawn(args, prompt, cwd)
    try:
        async with asyncio.timeout(timeout):
            async for line in proc.stdout:
                parse_event(line, run)
            await proc.wait()
    except TimeoutError:
        raise CliError(504, "Timeout do CLI")
    finally:
        if proc.returncode is None:
            proc.kill()
        await proc.wait()
        shutil.rmtree(cwd, ignore_errors=True)
    if run.error:
        raise CliError(502, run.error)
    if not run.usage and not run.text:
        raise CliError(502, "O CLI terminou sem resposta")
    return run


def usage_of(u: dict) -> dict:
    p, c = u.get("input_tokens", 0), u.get("output_tokens", 0)
    return {"prompt_tokens": p, "completion_tokens": c, "total_tokens": p + c}


# --------------------------------------------------------------------------- chat

def fetch_url(url: str) -> bytes:
    if url.startswith("data:"):
        return base64.b64decode(url.split(",", 1)[1])
    with urllib.request.urlopen(url, timeout=30) as r:  # noqa: S310
        return r.read()


async def message_text(content: Any, tmp: Path, images: list[Path]) -> str:
    """Extrai o texto de uma mensagem e grava imagens (image_url) em `tmp`."""
    if isinstance(content, str):
        return content
    parts: list[str] = []
    for p in content if isinstance(content, list) else []:
        if not isinstance(p, dict):
            continue
        if p.get("type") in ("text", "input_text"):
            parts.append(p.get("text", ""))
        elif p.get("type") in ("image_url", "input_image"):
            u = p.get("image_url")
            url = u.get("url") if isinstance(u, dict) else u
            if url:
                try:
                    path = tmp / f"img{len(images)}.bin"
                    path.write_bytes(await asyncio.to_thread(fetch_url, url))
                    images.append(path)
                    parts.append(f"[imagem anexada #{len(images)}]")
                except Exception as e:  # noqa: BLE001
                    raise CliError(400, f"Não foi possível obter a imagem: {e}")
    return "\n".join(parts)


async def build_prompt(messages: list[dict], tmp: Path, images: list[Path]) -> str:
    system, turns = [], []
    for m in messages:
        text = await message_text(m.get("content"), tmp, images)
        (system if m.get("role") in ("system", "developer") else turns).append((m.get("role"), text))
    intro = ("Você é um assistente de chat. Responda diretamente em texto, sem executar comandos, "
             "sem ler ou alterar arquivos e sem usar ferramentas.")
    if system:
        intro += "\n\nInstruções do sistema:\n" + "\n\n".join(t for _, t in system)
    if len(turns) == 1 and turns[0][0] == "user":
        return f"{intro}\n\n{turns[0][1]}"
    names = {"user": "Usuário", "assistant": "Assistente"}
    lines = [f"{names.get(r, r)}: {t}" for r, t in turns]
    return (f"{intro}\n\nConversa até agora:\n\n" + "\n\n".join(lines)
            + "\n\nResponda apenas a próxima mensagem do Assistente.")


@app.get("/health")
async def health() -> dict:
    return {"status": "ok"}


@app.get("/v1/models", dependencies=[Depends(auth)])
async def models() -> dict:
    return {"object": "list", "data": [
        {"id": m, "object": "model", "created": 0, "owned_by": "openai"} for m in MODELS + IMAGE_MODELS]}


@app.post("/v1/chat/completions", dependencies=[Depends(auth)])
async def chat(body: dict):
    model = body.get("model") or DEFAULT_MODEL
    if model in IMAGE_MODELS:
        return err(400, "Modelo de imagem: use /v1/images/generations")
    stream = bool(body.get("stream"))
    tmp, images = Path(tempfile.mkdtemp(prefix="msg-")), []
    try:
        prompt = await build_prompt(body.get("messages") or [], tmp, images)
    except CliError:
        shutil.rmtree(tmp, ignore_errors=True)
        raise
    if not prompt.strip() or not (body.get("messages") or []):
        shutil.rmtree(tmp, ignore_errors=True)
        return err(400, "messages vazio")
    args = cli_args(model, body.get("reasoning_effort"), images, image_gen=False)
    cid, created = f"chatcmpl-{uuid.uuid4().hex}", int(time.time())

    async def execute() -> Run:
        try:
            async with sem:
                return await run_codex(args, prompt, TIMEOUT)
        finally:
            shutil.rmtree(tmp, ignore_errors=True)

    if not stream:
        run = await execute()
        return {"id": cid, "object": "chat.completion", "created": created, "model": model or "codex",
                "choices": [{"index": 0, "finish_reason": "stop",
                             "message": {"role": "assistant", "content": run.text}}],
                "usage": usage_of(run.usage)}

    def chunk(delta: dict, finish: str | None = None, usage: dict | None = None) -> str:
        d = {"id": cid, "object": "chat.completion.chunk", "created": created, "model": model or "codex",
             "choices": [{"index": 0, "delta": delta, "finish_reason": finish}]}
        if usage:
            d["usage"] = usage
        return f"data: {json.dumps(d)}\n\n"

    async def gen() -> AsyncIterator[str]:
        # O CLI só entrega a mensagem pronta (sem deltas): enviamos tudo num chunk,
        # com comentários SSE de keep-alive enquanto o modelo trabalha.
        yield chunk({"role": "assistant", "content": ""})
        task = asyncio.create_task(execute())
        try:
            while not task.done():
                await asyncio.wait({task}, timeout=10)
                if not task.done():
                    yield ": keep-alive\n\n"
            run = task.result()
            yield chunk({"content": run.text})
            yield chunk({}, "stop", usage_of(run.usage))
        except CliError as e:
            yield f"data: {json.dumps({'error': {'message': e.msg}})}\n\n"
        finally:
            if not task.done():
                task.cancel()
        yield "data: [DONE]\n\n"

    return StreamingResponse(gen(), media_type="text/event-stream")


# --------------------------------------------------------------------------- imagens

def parse_size(size: str | None) -> tuple[int, int] | None:
    try:
        w, h = (int(x) for x in (size or "").lower().split("x"))
        return (w, h) if w > 0 and h > 0 else None
    except ValueError:
        return None


def orientation_hint(size: tuple[int, int] | None) -> str:
    if not size:
        return ""
    w, h = size
    shape = "quadrada" if w == h else "horizontal (paisagem)" if w > h else "vertical (retrato)"
    return f" Composição {shape}, proporção aproximada {w}:{h}."


def finish_image(raw: bytes, size: tuple[int, int] | None, fmt: str) -> tuple[bytes, str]:
    """Ajusta ao tamanho pedido (corte centralizado + redimensionamento) e ao formato."""
    img = Image.open(io.BytesIO(raw))
    if size and img.size != size:
        tw, th = size
        sw, sh = img.size
        scale = max(tw / sw, th / sh)
        img = img.resize((max(tw, round(sw * scale)), max(th, round(sh * scale))), Image.LANCZOS)
        left, top = (img.width - tw) // 2, (img.height - th) // 2
        img = img.crop((left, top, left + tw, top + th))
    pil_fmt, mime = FORMATS.get(fmt, FORMATS["png"])
    if pil_fmt == "JPEG" and img.mode in ("RGBA", "LA", "P"):
        img = img.convert("RGB")
    out = io.BytesIO()
    img.save(out, pil_fmt)
    return out.getvalue(), mime


def collect_generated(thread_id: str) -> bytes:
    d = CODEX_HOME / "generated_images" / thread_id
    files = sorted(d.glob("*"), key=lambda p: p.stat().st_mtime) if d.is_dir() else []
    if not files:
        return b""
    data = files[-1].read_bytes()
    shutil.rmtree(d, ignore_errors=True)
    return data


async def make_image(prompt: str, refs: list[Path], effort: str | None) -> tuple[bytes, dict]:
    async with sem:
        run = await run_codex(cli_args(DEFAULT_MODEL, effort, refs, image_gen=True), prompt, IMAGE_TIMEOUT)
    data = collect_generated(run.thread_id)
    if not data:
        raise CliError(502, run.text or "O CLI não gerou nenhuma imagem")
    return data, run.usage


def purge_files() -> None:
    now = time.time()
    for f in FILES_DIR.glob("*"):
        if now - f.stat().st_mtime > FILES_TTL:
            f.unlink(missing_ok=True)


def image_payload(req: Request, raw: bytes, mime: str, response_format: str) -> dict:
    if response_format == "url":
        purge_files()
        name = f"{uuid.uuid4().hex}.{mime.split('/')[1]}"
        (FILES_DIR / name).write_bytes(raw)
        base = PUBLIC_URL or str(req.base_url).rstrip("/")
        return {"url": f"{base}/v1/files/{name}"}
    return {"b64_json": base64.b64encode(raw).decode()}


async def respond_images(req: Request, prompts: list[str], refs: list[Path], opts: dict) -> dict:
    n = max(1, min(int(opts.get("n") or 1), 4))
    size = parse_size(opts.get("size"))
    fmt = opts.get("output_format") or "png"
    fmt = "jpeg" if fmt == "jpg" else fmt
    results = await asyncio.gather(*(make_image(prompts[0], refs, opts.get("reasoning_effort")) for _ in range(n)))
    data, usage = [], {"input_tokens": 0, "output_tokens": 0}
    for raw, u in results:
        out, mime = await asyncio.to_thread(finish_image, raw, size, fmt)
        item = image_payload(req, out, mime, opts.get("response_format") or "b64_json")
        data.append(item)
        usage["input_tokens"] += u.get("input_tokens", 0)
        usage["output_tokens"] += u.get("output_tokens", 0)
    usage["total_tokens"] = usage["input_tokens"] + usage["output_tokens"]
    return {"created": int(time.time()), "data": data, "usage": usage}


GEN_TEMPLATE = ("Use a ferramenta de geração de imagem (imagegen) para criar UMA imagem nova com a descrição abaixo."
                "{hint} Não escreva nenhum arquivo na pasta de trabalho e responda apenas \"ok\" ao terminar.\n\n"
                "Descrição:\n{prompt}")
EDIT_TEMPLATE = ("Use a ferramenta de geração de imagem (imagegen) no modo de EDIÇÃO para produzir UMA imagem nova a partir "
                 "da(s) imagem(ns) anexada(s), aplicando a instrução abaixo e preservando o que não for mencionado."
                 "{hint}{mask} Não escreva nenhum arquivo na pasta de trabalho e responda apenas \"ok\" ao terminar.\n\n"
                 "Instrução:\n{prompt}")


@app.post("/v1/images/generations", dependencies=[Depends(auth)])
async def image_generations(request: Request, body: dict):
    prompt = (body.get("prompt") or "").strip()
    if not prompt:
        return err(400, "prompt vazio")
    full = GEN_TEMPLATE.format(hint=orientation_hint(parse_size(body.get("size"))), prompt=prompt)
    return await respond_images(request, [full], [], body)


@app.post("/v1/images/edits", dependencies=[Depends(auth)])
async def image_edits(request: Request):
    form = await request.form()
    prompt = str(form.get("prompt") or "").strip()
    uploads = [v for k, v in form.multi_items() if k in ("image", "image[]") and hasattr(v, "read")]
    mask = form.get("mask")
    if not prompt or not uploads:
        return err(400, "prompt e image são obrigatórios")
    tmp = Path(tempfile.mkdtemp(prefix="edit-"))
    try:
        refs: list[Path] = []
        for i, up in enumerate(uploads):
            p = tmp / f"imagem{i}.png"
            Image.open(io.BytesIO(await up.read())).save(p, "PNG")
            refs.append(p)
        mask_note = ""
        if mask is not None and hasattr(mask, "read"):
            p = tmp / "mascara.png"
            Image.open(io.BytesIO(await mask.read())).save(p, "PNG")
            refs.append(p)
            mask_note = (" A ÚLTIMA imagem anexada é uma máscara: as áreas transparentes (ou pretas) indicam "
                         "exatamente a região que deve ser alterada; o restante deve permanecer idêntico.")
        opts = {k: form.get(k) for k in ("n", "size", "response_format", "output_format", "reasoning_effort")}
        size = parse_size(opts["size"])
        full = EDIT_TEMPLATE.format(hint=orientation_hint(size), mask=mask_note, prompt=prompt)
        return await respond_images(request, [full], refs, opts)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


@app.get("/v1/files/{name}")
async def files(name: str):
    path = FILES_DIR / Path(name).name
    if not path.is_file():
        raise HTTPException(404, "Arquivo expirado ou inexistente")
    return FileResponse(path)
