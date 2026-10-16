"""Gateway compatível com a API da OpenAI que delega ao CLI `claude` (login de conta, sem API key)."""
import asyncio
import json
import os
import time
import uuid
from typing import Any, AsyncIterator

from fastapi import Depends, FastAPI, Header, HTTPException
from fastapi.responses import JSONResponse, StreamingResponse

GATEWAY_TOKEN = os.getenv("GATEWAY_TOKEN", "")  # opcional: protege este gateway (não é chave da Anthropic)
DEFAULT_MODEL = os.getenv("CLAUDE_DEFAULT_MODEL", "sonnet")
TIMEOUT = int(os.getenv("CLAUDE_TIMEOUT", "300"))
MAX_CONCURRENCY = int(os.getenv("CLAUDE_MAX_CONCURRENCY", "2"))
MODELS = [m.strip() for m in os.getenv("CLAUDE_MODELS", "opus,sonnet,haiku").split(",") if m.strip()]

app = FastAPI(title="Claude Gateway")
sem = asyncio.Semaphore(MAX_CONCURRENCY)


def auth(authorization: str | None = Header(default=None)) -> None:
    if GATEWAY_TOKEN and authorization != f"Bearer {GATEWAY_TOKEN}":
        raise HTTPException(401, "Token inválido")


def text_of(content: Any) -> str:
    if isinstance(content, str):
        return content
    if isinstance(content, list):
        return "\n".join(p.get("text", "") for p in content if isinstance(p, dict) and p.get("type") == "text")
    return ""


def build_prompt(messages: list[dict]) -> tuple[str, str]:
    system = "\n\n".join(text_of(m.get("content")) for m in messages if m.get("role") in ("system", "developer"))
    turns = [m for m in messages if m.get("role") not in ("system", "developer")]
    if len(turns) == 1 and turns[0].get("role") == "user":
        return system, text_of(turns[0].get("content"))
    names = {"user": "Usuário", "assistant": "Assistente"}
    lines = [f"{names.get(m.get('role'), m.get('role'))}: {text_of(m.get('content'))}" for m in turns]
    return system, "Conversa até agora:\n\n" + "\n\n".join(lines) + "\n\nResponda apenas a próxima mensagem do Assistente."


def cli_args(model: str, system: str, stream: bool) -> list[str]:
    args = ["claude", "-p", "--model", model, "--tools", "", "--no-session-persistence",
            "--disable-slash-commands", "--output-format", "stream-json" if stream else "json"]
    if stream:
        args += ["--verbose", "--include-partial-messages"]
    if system:
        args += ["--system-prompt", system]
    return args


def child_env() -> dict[str, str]:
    env = dict(os.environ)
    env.pop("ANTHROPIC_API_KEY", None)  # garante uso da conta logada
    return env


async def spawn(args: list[str], prompt: str) -> asyncio.subprocess.Process:
    proc = await asyncio.create_subprocess_exec(
        *args, stdin=asyncio.subprocess.PIPE, stdout=asyncio.subprocess.PIPE,
        stderr=asyncio.subprocess.PIPE, env=child_env(), limit=16 * 1024 * 1024)
    proc.stdin.write(prompt.encode())
    await proc.stdin.drain()
    proc.stdin.close()
    return proc


def usage_of(u: dict | None) -> dict:
    u = u or {}
    p = u.get("input_tokens", 0) + u.get("cache_creation_input_tokens", 0) + u.get("cache_read_input_tokens", 0)
    c = u.get("output_tokens", 0)
    return {"prompt_tokens": p, "completion_tokens": c, "total_tokens": p + c}


def err(status: int, msg: str) -> JSONResponse:
    return JSONResponse({"error": {"message": msg, "type": "claude_cli_error"}}, status_code=status)


@app.get("/health")
async def health() -> dict:
    return {"status": "ok"}


@app.get("/v1/models", dependencies=[Depends(auth)])
async def models() -> dict:
    return {"object": "list", "data": [
        {"id": m, "object": "model", "created": 0, "owned_by": "anthropic"} for m in MODELS]}


@app.post("/v1/chat/completions", dependencies=[Depends(auth)])
async def chat(body: dict):
    model = body.get("model") or DEFAULT_MODEL
    system, prompt = build_prompt(body.get("messages") or [])
    if not prompt.strip():
        return err(400, "messages vazio")
    stream = bool(body.get("stream"))
    cid, created = f"chatcmpl-{uuid.uuid4().hex}", int(time.time())

    def chunk(delta: dict, finish: str | None = None, usage: dict | None = None) -> str:
        d = {"id": cid, "object": "chat.completion.chunk", "created": created, "model": model,
             "choices": [{"index": 0, "delta": delta, "finish_reason": finish}]}
        if usage:
            d["usage"] = usage
        return f"data: {json.dumps(d)}\n\n"

    if not stream:
        async with sem:
            proc = await spawn(cli_args(model, system, False), prompt)
            try:
                out, errb = await asyncio.wait_for(proc.communicate(), TIMEOUT)
            except asyncio.TimeoutError:
                proc.kill()
                return err(504, "Timeout do CLI")
        try:
            r = json.loads(out)
        except ValueError:
            return err(502, (errb or out).decode()[:500] or "Resposta inválida do CLI")
        if r.get("is_error"):
            return err(502, str(r.get("result")))
        return {"id": cid, "object": "chat.completion", "created": created, "model": model,
                "choices": [{"index": 0, "finish_reason": "stop",
                             "message": {"role": "assistant", "content": r.get("result", "")}}],
                "usage": usage_of(r.get("usage"))}

    async def gen() -> AsyncIterator[str]:
        async with sem:
            proc = await spawn(cli_args(model, system, True), prompt)
            usage, failed = None, None
            try:
                yield chunk({"role": "assistant", "content": ""})
                async with asyncio.timeout(TIMEOUT):
                    async for line in proc.stdout:
                        try:
                            ev = json.loads(line)
                        except ValueError:
                            continue
                        if ev.get("type") == "stream_event":
                            d = ev["event"].get("delta") or {}
                            if d.get("type") == "text_delta":
                                yield chunk({"content": d["text"]})
                        elif ev.get("type") == "result":
                            usage = usage_of(ev.get("usage"))
                            if ev.get("is_error"):
                                failed = str(ev.get("result"))
                if failed:
                    yield f"data: {json.dumps({'error': {'message': failed}})}\n\n"
                yield chunk({}, "stop", usage)
                yield "data: [DONE]\n\n"
            finally:
                if proc.returncode is None:
                    proc.kill()
                await proc.wait()

    return StreamingResponse(gen(), media_type="text/event-stream")
