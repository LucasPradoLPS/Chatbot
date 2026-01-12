import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http:///host.docker.internal:8000/api/whatsapp/webhook';
const INSTANCE = __ENV.INSTANCE || 'N8n';
const RATE_LIMIT = Number(__ENV.RATE_LIMIT || 50); // msgs/sec alvo para cenário burst
const SOAK_DURATION = __ENV.SOAK_DURATION || '10m';

// Templates de payload parecidos com o Evolution
const templates = [
  {
    event: 'messages.upsert',
    data: {
      key: { remoteJid: '5511999999999@s.whatsapp.net', fromMe: false },
      message: { conversation: 'Oi, quero saber mais.' },
      status: 'PENDING',
      source: 'evolution',
    },
  },
  {
    event: 'messages.upsert',
    data: {
      key: { remoteJid: '5511888888888@s.whatsapp.net', fromMe: false },
      message: { extendedTextMessage: { text: 'Mensagem longa '.repeat(20) } },
      source: 'evolution',
    },
  },
  {
    // Mensagem em outro idioma + caracteres especiais
    event: 'messages.upsert',
    data: {
      key: { remoteJid: '351911111111@s.whatsapp.net', fromMe: false },
      message: { conversation: 'Olá! ¿Cómo estás? Bonjour!' },
      source: 'evolution',
    },
  },
  {
    // Payload mínimo válido
    event: 'messages.upsert',
    data: {
      key: { remoteJid: '5511777777777@s.whatsapp.net', fromMe: false },
      message: { conversation: 'Ping' },
    },
  },
  {
    // Payload malformado (para observar respostas de erro)
    event: 'messages.upsert',
    data: {
      key: { fromMe: false },
      message: {},
    },
  },
];

function makePayload() {
  const t = templates[Math.floor(Math.random() * templates.length)];
  const id = `${__VU}-${Date.now()}-${Math.floor(Math.random() * 1e6)}`;
  return {
    instance: INSTANCE,
    event: t.event,
    data: {
      ...t.data,
      key: { ...t.data.key, id },
    },
  };
}

export const options = {
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<1500', 'p(99)<3000'],
  },
  scenarios: {
    spike: {
      executor: 'constant-arrival-rate',
      rate: RATE_LIMIT,
      timeUnit: '1s',
      duration: '2m',
      preAllocatedVUs: 20,
      maxVUs: 200,
    },
    ramp: {
      executor: 'ramping-arrival-rate',
      startRate: 10,
      timeUnit: '1s',
      stages: [
        { target: RATE_LIMIT, duration: '3m' },
        { target: RATE_LIMIT * 2, duration: '2m' },
        { target: 0, duration: '1m' },
      ],
      preAllocatedVUs: 20,
      maxVUs: 250,
    },
    soak: {
      executor: 'per-vu-iterations',
      vus: 10,
      iterations: 1000,
      maxDuration: SOAK_DURATION,
    },
  },
};

export default function () {
  const payload = makePayload();
  const res = http.post(BASE_URL, JSON.stringify(payload), {
    headers: { 'Content-Type': 'application/json' },
    timeout: '30s',
  });

  check(res, {
    'status 202/200/400': (r) => [200, 202, 400].includes(r.status),
  });

  sleep(Math.random() * 0.3);
}
