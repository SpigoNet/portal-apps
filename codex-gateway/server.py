"""Gateway compatível com a API da OpenAI que delega ao CLI `codex` (login de conta, sem API key).

Endpoints: /v1/models, /v1/chat/completions (texto e visão), /v1/images/generations e /v1/images/edits
(parâmetros de imagem: prompt, n, size, quality, background, output_format, output_compression, moderation,
style, input_fidelity, mask, response_format, stream, reasoning_effort).
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
from PIL import Image, ImageChops

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
#
# A ferramenta `imagegen` do Codex só recebe o prompt (e imagens de referência): não há parâmetros nativos.
# Os parâmetros da API de imagens da OpenAI são atendidos assim:
#   - prompt:  quality, style, moderation, input_fidelity, background=transparent, orientação/proporção
#   - pós-processamento (Pillow): size, output_format, output_compression, background=transparent

KEY_COLOR = (0, 255, 0)  # cor de fundo pedida ao modelo para recortar o fundo (background=transparent)
MIN_SIDE, MAX_SIDE = 16, int(os.getenv("GATEWAY_IMAGE_MAX_SIDE", "4096"))
MAX_N = int(os.getenv("GATEWAY_IMAGE_MAX_N", "10"))
QUALITY_HINTS = {
    "low": " Qualidade: rascunho rápido, detalhes simples e poucos elementos.",
    "medium": " Qualidade: equilibrada, com bom nível de detalhe.",
    "high": " Qualidade: máxima, com detalhes ricos, texturas nítidas e acabamento primoroso.",
    "auto": "",
}
STYLE_HINTS = {
    "vivid": " Estilo: vívido, com cores intensas, alto contraste e aspecto dramático.",
    "natural": " Estilo: natural, com cores e iluminação realistas e discretas, sem exagero.",
}
FIDELITY_HINTS = {
    "high": " Fidelidade alta: preserve com precisão rostos, traços, texturas, logotipos e demais detalhes das imagens de entrada.",
    "low": "",
}
MODERATION_HINTS = {"low": " Aplique uma moderação de conteúdo menos restritiva dentro do que a política permitir."}
BACKGROUND_HINTS = {
    "transparent": (" O fundo deve ser UMA cor chapada e uniforme verde puro (#00FF00), sem gradiente, sombras no chão, "
                    "reflexos ou textura, para ser recortado depois; o sujeito NÃO pode usar essa cor de verde."),
    "opaque": " O fundo deve ser opaco e totalmente preenchido, sem transparência.",
}
ENUMS = {
    "quality": ("auto", "low", "medium", "high"),
    "background": ("auto", "transparent", "opaque"),
    "moderation": ("auto", "low"),
    "style": ("vivid", "natural"),
    "input_fidelity": ("low", "high"),
    "response_format": ("b64_json", "url"),
    "output_format": ("png", "jpeg", "webp"),
    "reasoning_effort": ("minimal", "low", "medium", "high", "xhigh"),
}


def blank(v: Any) -> bool:
    return v is None or (isinstance(v, str) and not v.strip())


def parse_size(size: str | None) -> tuple[int, int] | None:
    try:
        w, h = (int(x) for x in (size or "").lower().strip().split("x"))
        return (w, h) if w > 0 and h > 0 else None
    except ValueError:
        return None


def validate_options(raw: dict) -> dict:
    """Normaliza e valida os parâmetros (JSON ou multipart); levanta CliError(400) se inválidos."""
    o: dict[str, Any] = {}
    for key, allowed in ENUMS.items():
        v = raw.get(key)
        if blank(v):
            continue
        v = str(v).strip().lower()
        if key == "output_format" and v == "jpg":
            v = "jpeg"
        if v not in allowed:
            raise CliError(400, f"Valor inválido para '{key}': {v}. Aceitos: {', '.join(allowed)}")
        o[key] = v
    try:
        n = int(raw.get("n") if not blank(raw.get("n")) else 1)
    except (TypeError, ValueError):
        raise CliError(400, "'n' deve ser um inteiro")
    if not 1 <= n <= MAX_N:
        raise CliError(400, f"'n' deve estar entre 1 e {MAX_N}")
    o["n"] = n

    size = raw.get("size")
    o["size"] = None
    if not blank(size) and str(size).strip().lower() != "auto":
        o["size"] = parse_size(str(size))
        if not o["size"] or not all(MIN_SIDE <= s <= MAX_SIDE for s in o["size"]):
            raise CliError(400, f"'size' inválido: use 'auto' ou LARGURAxALTURA (cada lado de {MIN_SIDE} a {MAX_SIDE})")

    comp = raw.get("output_compression")
    o["output_compression"] = None
    if not blank(comp):
        try:
            o["output_compression"] = int(comp)
        except (TypeError, ValueError):
            raise CliError(400, "'output_compression' deve ser um inteiro de 0 a 100")
        if not 0 <= o["output_compression"] <= 100:
            raise CliError(400, "'output_compression' deve estar entre 0 e 100")

    o.setdefault("output_format", "png")
    o.setdefault("response_format", "b64_json")
    if o.get("background") == "transparent" and o["output_format"] == "jpeg":
        raise CliError(400, "background=transparent exige output_format png ou webp")
    o["stream"] = str(raw.get("stream") or "").lower() in ("1", "true", "yes")
    # aceitos por compatibilidade, sem efeito: model (o CLI escolhe), user, partial_images
    return o


def orientation_hint(size: tuple[int, int] | None) -> str:
    if not size:
        return ""
    w, h = size
    shape = "quadrada" if w == h else "horizontal (paisagem)" if w > h else "vertical (retrato)"
    return f" Composição {shape}, proporção aproximada {w}:{h}."


def option_hints(o: dict, edit: bool) -> str:
    hint = orientation_hint(o["size"])
    hint += QUALITY_HINTS.get(o.get("quality", "auto"), "")
    hint += STYLE_HINTS.get(o.get("style", ""), "")
    hint += BACKGROUND_HINTS.get(o.get("background", ""), "")
    hint += MODERATION_HINTS.get(o.get("moderation", ""), "")
    if edit:
        hint += FIDELITY_HINTS.get(o.get("input_fidelity", ""), "")
    return hint


def remove_key_color(img: Image.Image) -> Image.Image:
    """Transforma o fundo verde chapado em transparência (com suavização de borda e remoção do respingo verde)."""
    rgb = img.convert("RGB")
    r, g, b = rgb.split()
    dominance = ImageChops.subtract(g, ImageChops.lighter(r, b))  # quanto o verde excede os outros canais
    alpha = dominance.point(lambda d: 255 - min(255, max(0, (d - 40) * 4)))
    despilled_g = ImageChops.darker(g, ImageChops.lighter(r, b))
    clean = Image.merge("RGB", (r, despilled_g, b))
    out = Image.composite(rgb, clean, alpha.point(lambda a: 255 if a == 255 else 0))  # só mexe nas bordas
    out.putalpha(alpha)
    return out


def finish_image(raw: bytes, o: dict) -> tuple[bytes, str]:
    """Aplica tamanho (corte centralizado + redimensionamento), transparência, formato e compressão."""
    img = Image.open(io.BytesIO(raw))
    size = o["size"]
    if size and img.size != size:
        tw, th = size
        sw, sh = img.size
        scale = max(tw / sw, th / sh)
        img = img.resize((max(tw, round(sw * scale)), max(th, round(sh * scale))), Image.LANCZOS)
        left, top = (img.width - tw) // 2, (img.height - th) // 2
        img = img.crop((left, top, left + tw, top + th))
    if o.get("background") == "transparent":
        img = remove_key_color(img)
    pil_fmt, mime = FORMATS[o["output_format"]]
    if pil_fmt == "JPEG" and img.mode != "RGB":
        img = img.convert("RGB")
    kwargs: dict[str, Any] = {}
    comp = o["output_compression"]
    if pil_fmt == "JPEG":
        kwargs = {"quality": 95 if comp is None else max(1, comp)}
    elif pil_fmt == "WEBP":
        kwargs = {"quality": 95 if comp is None else comp}
    elif pil_fmt == "PNG" and comp is not None:
        kwargs = {"compress_level": round(comp * 9 / 100)}
    out = io.BytesIO()
    img.save(out, pil_fmt, **kwargs)
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


async def one_image(req: Request, prompt: str, refs: list[Path], o: dict) -> tuple[dict, dict]:
    raw, usage = await make_image(prompt, refs, o.get("reasoning_effort"))
    out, mime = await asyncio.to_thread(finish_image, raw, o)
    return image_payload(req, out, mime, o["response_format"]), usage


def totals(usages: list[dict]) -> dict:
    i, p = sum(u.get("input_tokens", 0) for u in usages), sum(u.get("output_tokens", 0) for u in usages)
    return {"input_tokens": i, "output_tokens": p, "total_tokens": i + p}


def response_meta(o: dict) -> dict:
    return {"background": o.get("background", "auto"), "output_format": o["output_format"],
            "quality": o.get("quality", "auto"), "size": "{}x{}".format(*o["size"]) if o["size"] else "auto"}


async def respond_images(req: Request, prompt: str, refs: list[Path], o: dict, tmp: Path):
    """`tmp` (pasta das referências) é apagada ao terminar, inclusive no modo stream."""
    if not o["stream"]:
        try:
            results = await asyncio.gather(*(one_image(req, prompt, refs, o) for _ in range(o["n"])))
        finally:
            shutil.rmtree(tmp, ignore_errors=True)
        return {"created": int(time.time()), "data": [r[0] for r in results],
                "usage": totals([r[1] for r in results]), **response_meta(o)}

    # stream: um evento `image_generation.completed` por imagem, com keep-alive enquanto o CLI trabalha
    tasks = [asyncio.create_task(one_image(req, prompt, refs, o)) for _ in range(o["n"])]

    async def gen() -> AsyncIterator[str]:
        try:
            pending = set(tasks)
            while pending:
                done, pending = await asyncio.wait(pending, timeout=10, return_when=asyncio.FIRST_COMPLETED)
                if not done:
                    yield ": keep-alive\n\n"
                for t in done:
                    item, usage = t.result()
                    ev = {"type": "image_generation.completed", "created_at": int(time.time()), **item,
                          "usage": totals([usage]), **response_meta(o)}
                    yield f"event: image_generation.completed\ndata: {json.dumps(ev)}\n\n"
        except CliError as e:
            yield f"event: error\ndata: {json.dumps({'error': {'message': e.msg}})}\n\n"
        finally:
            for t in tasks:
                t.cancel()
            shutil.rmtree(tmp, ignore_errors=True)
        yield "data: [DONE]\n\n"

    return StreamingResponse(gen(), media_type="text/event-stream")


GEN_TEMPLATE = ("Use a ferramenta de geração de imagem (imagegen) para criar UMA imagem nova com a descrição abaixo."
                "{hint} Não escreva nenhum arquivo na pasta de trabalho e responda apenas \"ok\" ao terminar.\n\n"
                "Descrição:\n{prompt}")
EDIT_TEMPLATE = ("Use a ferramenta de geração de imagem (imagegen) no modo de EDIÇÃO para produzir UMA imagem nova a partir "
                 "da(s) imagem(ns) anexada(s), aplicando a instrução abaixo e preservando o que não for mencionado."
                 "{hint}{mask} Não escreva nenhum arquivo na pasta de trabalho e responda apenas \"ok\" ao terminar.\n\n"
                 "Instrução:\n{prompt}")
MASK_NOTE = (" A ÚLTIMA imagem anexada é uma máscara: as áreas transparentes (ou pretas) indicam "
             "exatamente a região que deve ser alterada; o restante deve permanecer idêntico.")


def to_png(path: Path, data: bytes) -> None:
    try:
        Image.open(io.BytesIO(data)).save(path, "PNG")
    except Exception as e:  # noqa: BLE001
        raise CliError(400, f"Imagem inválida ({path.name}): {e}")


def as_list(v: Any) -> list:
    return v if isinstance(v, list) else [] if v is None else [v]


def ref_url(v: Any) -> str | None:
    """Aceita string (URL/data URL) ou objeto {image_url: ...} / {url: ...}."""
    if isinstance(v, dict):
        v = v.get("image_url") or v.get("url")
        if isinstance(v, dict):
            v = v.get("url")
    return v if isinstance(v, str) and v else None


@app.post("/v1/images/generations", dependencies=[Depends(auth)])
async def image_generations(request: Request, body: dict):
    prompt = (body.get("prompt") or "").strip()
    if not prompt:
        return err(400, "prompt vazio")
    o = validate_options(body)
    # extensão: "images" (URLs ou data URLs) como referências visuais numa geração
    urls = [u for u in (ref_url(x) for x in as_list(body.get("images") or body.get("image"))) if u]
    tmp = Path(tempfile.mkdtemp(prefix="gen-"))
    try:
        refs: list[Path] = []
        for i, u in enumerate(urls[:16]):
            try:
                data = await asyncio.to_thread(fetch_url, u)
            except Exception as e:  # noqa: BLE001
                raise CliError(400, f"Não foi possível obter a imagem de referência: {e}")
            to_png(tmp / f"ref{i}.png", data)
            refs.append(tmp / f"ref{i}.png")
        hint = option_hints(o, edit=False) + (" Use as imagens anexadas como referência visual." if refs else "")
        return await respond_images(request, GEN_TEMPLATE.format(hint=hint, prompt=prompt), refs, o, tmp)
    except BaseException:
        shutil.rmtree(tmp, ignore_errors=True)
        raise


@app.post("/v1/images/edits", dependencies=[Depends(auth)])
async def image_edits(request: Request):
    ctype = request.headers.get("content-type", "")
    if ctype.startswith("application/json"):  # variante JSON: images/mask como URL ou data URL
        body = await request.json()
        prompt = str(body.get("prompt") or "").strip()
        sources = [ref_url(x) for x in as_list(body.get("images") or body.get("image"))]
        mask_src = ref_url(body.get("mask"))
        raw_opts = body
        blobs = [await asyncio.to_thread(fetch_url, u) for u in sources if u]
        mask_blob = await asyncio.to_thread(fetch_url, mask_src) if mask_src else None
    else:
        form = await request.form()
        prompt = str(form.get("prompt") or "").strip()
        uploads = [v for k, v in form.multi_items() if k in ("image", "image[]") and hasattr(v, "read")]
        blobs = [await u.read() for u in uploads]
        mask = form.get("mask")
        mask_blob = await mask.read() if mask is not None and hasattr(mask, "read") else None
        raw_opts = dict(form.items())
    if not prompt or not blobs:
        return err(400, "prompt e image são obrigatórios")
    o = validate_options(raw_opts)
    tmp = Path(tempfile.mkdtemp(prefix="edit-"))
    try:
        refs: list[Path] = []
        for i, data in enumerate(blobs):
            to_png(tmp / f"imagem{i}.png", data)
            refs.append(tmp / f"imagem{i}.png")
        if mask_blob:
            to_png(tmp / "mascara.png", mask_blob)
            refs.append(tmp / "mascara.png")
        full = EDIT_TEMPLATE.format(hint=option_hints(o, edit=True), mask=MASK_NOTE if mask_blob else "", prompt=prompt)
        return await respond_images(request, full, refs, o, tmp)
    except BaseException:
        shutil.rmtree(tmp, ignore_errors=True)
        raise


@app.get("/v1/files/{name}")
async def files(name: str):
    path = FILES_DIR / Path(name).name
    if not path.is_file():
        raise HTTPException(404, "Arquivo expirado ou inexistente")
    return FileResponse(path)
