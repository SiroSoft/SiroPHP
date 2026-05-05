import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const VUS = Number(__ENV.VUS || 50);
const DURATION = __ENV.DURATION || '10s';
const SCENARIO = __ENV.SCENARIO || 'all';

const defaultExecutor = {
  executor: 'constant-vus',
  vus: VUS,
  duration: DURATION,
};

function withScenario(scenarioName) {
  return SCENARIO === 'all' || SCENARIO === scenarioName;
}

export const options = {
  scenarios: {
    ...(withScenario('root')
      ? {
          root: {
            ...defaultExecutor,
            exec: 'hitRoot',
            tags: { scenario: 'root', endpoint: 'root' },
          },
        }
      : {}),
    ...(withScenario('users')
      ? {
          users: {
            ...defaultExecutor,
            exec: 'hitUsers',
            tags: { scenario: 'users', endpoint: 'users' },
          },
        }
      : {}),
    ...(withScenario('users_cached')
      ? {
          users_cached: {
            ...defaultExecutor,
            exec: 'hitUsersCached',
            tags: { scenario: 'users_cached', endpoint: 'users_cached' },
          },
        }
      : {}),
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<20'],
    'http_req_duration{endpoint:root}': ['p(95)<20'],
    'http_req_duration{endpoint:users}': ['p(95)<20'],
    'http_req_duration{endpoint:users_cached}': ['p(95)<5'],
  },
};

export function hitRoot() {
  const rootRes = http.get(`${BASE_URL}/`, { tags: { endpoint: 'root' } });
  check(rootRes, { 'GET / status is 200': (r) => r.status === 200 });
  sleep(0.05);
}

export function hitUsers() {
  const usersRes = http.get(`${BASE_URL}/users?bench=${__VU}-${__ITER}`, {
    tags: { endpoint: 'users' },
  });
  check(usersRes, { 'GET /users status is 200': (r) => r.status === 200 });
  sleep(0.05);
}

export function hitUsersCached() {
  // one miss to warm route cache
  http.get(`${BASE_URL}/users?warm=${__VU}`, { tags: { endpoint: 'users_warm' } });
  const usersCachedRes = http.get(`${BASE_URL}/users`, { tags: { endpoint: 'users_cached' } });
  check(usersCachedRes, { 'GET /users (cached) status is 200': (r) => r.status === 200 });
  sleep(0.05);
}

export function handleSummary(data) {
  const reqs = data.metrics.http_reqs?.values?.count || 0;
  const duration = parseInt(DURATION, 10) || 10;
  const reqPerSec = (reqs / duration).toFixed(2);
  const latency = (data.metrics.http_req_duration?.values?.p95 || 0).toFixed(2);
  const errorRate = ((data.metrics.http_req_failed?.values?.rate || 0) * 100).toFixed(2);

  const output = [
    '=== Siro v0.7.1 k6 Benchmark ===',
    `Requests/sec: ${reqPerSec}`,
    `Latency (p95): ${latency} ms`,
    `Error rate: ${errorRate}%`,
    '',
  ].join('\n');

  return {
    stdout: output,
  };
}
