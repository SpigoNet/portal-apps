FROM node:22-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
        python3 python3-venv ca-certificates curl \
    && rm -rf /var/lib/apt/lists/*

# Claude Code CLI (autenticação por login da conta, sem API key)
RUN npm install -g @anthropic-ai/claude-code && npm cache clean --force

WORKDIR /app
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY server.py .

# Todo o estado do CLI (credenciais, ~/.claude.json) fica num único volume.
ENV CLAUDE_CONFIG_DIR=/data/claude
RUN mkdir -p /data/claude /workspace && chown -R node:node /data /workspace /app
USER node
WORKDIR /workspace
VOLUME /data/claude

EXPOSE 8000
CMD ["uvicorn", "--app-dir", "/app", "server:app", "--host", "0.0.0.0", "--port", "8000"]
