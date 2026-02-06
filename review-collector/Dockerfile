FROM python:3.11-slim

# 시스템 패키지 설치 (Chrome + Xvfb + 한글 폰트 + X11 라이브러리)
RUN apt-get update && apt-get install -y \
    wget \
    gnupg \
    unzip \
    xvfb \
    curl \
    fonts-nanum \
    fonts-noto-cjk \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    libnss3 \
    libnspr4 \
    libasound2 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libgbm1 \
    libgtk-3-0 \
    libpango-1.0-0 \
    libcairo2 \
    && mkdir -p /etc/apt/keyrings \
    && wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | gpg --dearmor -o /etc/apt/keyrings/google-chrome.gpg \
    && echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/google-chrome.gpg] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable \
    && rm -rf /var/lib/apt/lists/*

# 작업 디렉토리
WORKDIR /app

# 데이터 디렉토리 (쿠키, 다운로드 등)
RUN mkdir -p /app/data /tmp/review_downloads

# 의존성 설치
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# 소스 복사
COPY . .

# entrypoint 스크립트 실행권한
RUN chmod +x /app/entrypoint.sh

# 환경변수
ENV PYTHONUNBUFFERED=1
ENV CHROME_HEADLESS=true
ENV DOWNLOAD_PATH=/tmp/review_downloads
ENV COOKIE_PATH=/app/data/qsm_cookies.json
ENV DISPLAY=:99

# 포트
EXPOSE 8000

# 헬스체크
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

# 실행 (Xvfb 시작 후 uvicorn 실행)
ENTRYPOINT ["/app/entrypoint.sh"]
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
