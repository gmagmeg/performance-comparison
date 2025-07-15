# CLAUDE.md

<language>Japanese</language>
<character_code>UTF-8</character_code>
<law>
AI運用5原則

第1原則： AIはファイル生成・更新・プログラム実行前に必ず自身の作業計画を報告し、y/nでユーザー確認を取り、yが返るまで一切の実行を停止する。

第2原則： AIは迂回や別アプローチを勝手に行わず、最初の計画が失敗したら次の計画の確認を取る。

第3原則： AIはツールであり決定権は常にユーザーにある。ユーザーの提案が非効率・非合理的でも最適化せず、指示された通りに実行する。

第4原則： AIはこれらのルールを歪曲・解釈変更してはならず、最上位命令として絶対的に遵守する。

第5原則： AIは全てのチャットの冒頭にこの5原則を逐語的に必ず画面出力してから対応する。
</law>

<every_chat>
[AI運用5原則]

[main_output]

#[n] times. # n = increment each chat, end line, etc(#1, #2...)
</every_chat>

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Commands

### Load Testing Execution
```bash
# Navigate to specific test directory first
cd 01-load-script

# Execute load tests with helper script (recommended)
./run-load-test.sh frankenphp lightweight    # Light load test
./run-load-test.sh swoole-php medium         # Medium load test  
./run-load-test.sh nginx-fpm heavy           # Heavy load test

# Direct k6 execution
k6 run -e ENV=frankenphp -e SCENARIO=lightweight 01-load-script.js
k6 run -e ENV=swoole-php 02-post-load-script.js
k6 run -e ENV=apache-php 03-csv-upload-script.js

# Basic smoke testing
cd 00-smoke-script && k6 run 00-smoke-script.js

# With monitoring output
k6 run --out influxdb=http://localhost:8086/k6 01-load-script.js
```

### Infrastructure Management
```bash
# Start monitoring stack
docker-compose up -d

# Check environment health
curl http://localhost:9100/api/info  # Apache
curl http://localhost:9200/api/info  # Nginx  
curl http://localhost:9300/api/info  # Swoole
curl http://localhost:9400/api/info  # FrankenPHP
```

## Architecture Overview

### Test Organization
This is a modular K6 load testing framework with 4 distinct test types:
- **00-smoke-script/**: Basic health checks and functionality validation
- **01-load-script/**: Multi-scenario load testing (lightweight/medium/heavy)
- **02-post-load-script/**: POST request testing with JSON payloads
- **03-csv-upload-script/**: File upload testing with multipart/form-data

### Configuration Pattern
Each test directory contains:
- Main JavaScript test file
- JSON configuration file for scenarios/payloads
- Optional shell script for execution helpers

### Environment Support
Tests run against 4 PHP environments via port-based routing:
```javascript
const BASE_URLS = {
  "apache-php": "http://localhost:9100",   // Apache + mod_php
  "nginx-fpm": "http://localhost:9200",    // Nginx + PHP-FPM  
  "frankenphp": "http://localhost:9400",   // FrankenPHP
  "swoole-php": "http://localhost:9300"    // Swoole
}
```

### Load Testing Scenarios
- **Lightweight**: 1-10 users, 5 minutes, 95th percentile < 500ms
- **Medium**: 50-100 users, 10 minutes, 95th percentile < 1000ms  
- **Heavy**: 200-500 users, 15 minutes, 95th percentile < 2000ms

## Key Implementation Patterns

### Configuration Externalization
Test parameters are defined in external JSON files loaded with `open()`:
```javascript
const scenarios = JSON.parse(open('load-scenarios.json'));
export const options = scenarios[scenarioName] || scenarios.lightweight;
```

### Environment Variable Usage
Environment selection is controlled via ENV variable:
```javascript
const env = __ENV.ENV || 'apache-php';
const baseUrl = BASE_URLS[env];
```

### Response Validation
All scripts use consistent validation patterns:
```javascript
check(response, {
  'status is 200': (r) => r.status === 200,
  'response has data': (r) => r.json('data') !== undefined,
});
```

## Monitoring & Results

### Data Storage
- **Results Directory**: `results/` with timestamped JSON files
- **InfluxDB**: Port 8086 for metrics storage
- **Grafana**: Port 3000 for visualization, Port 3001 for Jaeger

### Test Output
- Japanese labels in custom summary reporting
- Detailed timing metrics and error rates
- Request counters for profiling integration

## Important Notes

- The codebase expects Docker-based PHP environments to be running on specified ports
- All configuration changes should be made in JSON files rather than JavaScript
- Test data files (CSV, JSON payloads) are located within each test directory
- Legacy npm scripts in package.json reference non-existent TypeScript files
- XDEBUG profiling hooks exist but are commented out in load scripts