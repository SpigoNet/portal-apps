FROM node:22-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
        python3 python3-venv ca-certificates curl bubblewrap \
    && rm -rf /var/lib/apt/lists/*

# Codex CLI da OpenAI (autenticação por login da conta ChatGPT, sem API key)
RUN npm install -g @openai/codex && npm cache clean --force

WORKDIR /app
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY server.py .

# Todo o estado do CLI (auth.json, imagens geradas, sessões) fica num único volume.
ENV CODEX_HOME=/data/codex
RUN mkdir -p /data/codex /workspace && chown -R node:node /data /workspace /app
USER node
WORKDIR /workspace
VOLUME /data/codex

EXPOSE 8000
CMD ["uvicorn", "--app-dir", "/app", "server:app", "--host", "0.0.0.0", "--port", "8000"]
