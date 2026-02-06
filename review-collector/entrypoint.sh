#!/bin/bash

# Xvfb 가상 디스플레이 시작
Xvfb :99 -screen 0 1920x1080x24 &
export DISPLAY=:99

# 잠시 대기
sleep 2

# 메인 애플리케이션 실행
exec "$@"
