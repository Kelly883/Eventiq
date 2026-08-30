/**
 * Self-check for the MSW mock layer. Runs the same handlers the browser
 * worker registers through msw/node + axios, so the mock API contract can be
 * verified in CI without booting a real backend.
 *
 *   npm run check:mocks
 */
import assert from 'node:assert/strict'
import axios from 'axios'
import { server } from '../src/mocks/server.js'
import { db } from '../src/mocks/db.js'

const client = axios.create({ baseURL: 'http://mock.local', validateStatus: () => true })

async function main() {
  server.listen({ onUnhandledRequest: 'error' })

  const checks = []
  const check = async (name, fn) => {
    try {
      await fn()
      checks.push(`PASS  ${name}`)
    } catch (err) {
      checks.push(`FAIL  ${name} -> ${err.message}`)
      process.exitCode = 1
    }
  }

  await check('GET /events returns seeded events in EventPublicResource shape', async () => {
    const res = await client.get('/events')
    assert.equal(res.status, 200)
    assert.ok(Array.isArray(res.data.data))
    assert.ok(res.data.data.length > 0)
    const ev = res.data.data[0]
    for (const key of ['id', 'title', 'start_datetime', 'venue_name', 'category', 'ticket_price']) {
      assert.ok(key in ev, `missing field: ${key}`)
    }
  })

  await check('GET /categories returns plain category name strings', async () => {
    const res = await client.get('/categories')
    assert.equal(res.status, 200)
    assert.ok(Array.isArray(res.data.data))
    assert.ok(res.data.data.every((c) => typeof c === 'string'))
  })

  await check('GET /events honours ?search=', async () => {
    const target = db.event.getAll()[0].title.split(' ')[0]
    const res = await client.get(`/events?search=${encodeURIComponent(target)}`)
    assert.equal(res.status, 200)
    assert.ok(res.data.data.length > 0)
    assert.ok(res.data.data.every((e) => e.title.toLowerCase().includes(target.toLowerCase())))
  })

  await check('GET /api/events works too (prefix variant)', async () => {
    const res = await client.get('/api/events')
    assert.equal(res.status, 200)
    assert.ok(Array.isArray(res.data.data))
  })

  await check('POST /newsletter/subscribe -> 201 on valid email', async () => {
    const res = await client.post('/newsletter/subscribe', { email: 'a@b.com' })
    assert.equal(res.status, 201)
  })

  await check('POST /newsletter/subscribe -> 422 on invalid email', async () => {
    const res = await client.post('/newsletter/subscribe', { email: 'nope' })
    assert.equal(res.status, 422)
  })

  await check('GET /orders/:id resolves a seeded UUID order', async () => {
    const id = db.order.getAll()[0].id
    const res = await client.get(`/orders/${id}`)
    assert.equal(res.status, 200)
    assert.equal(res.data.data.id, id)
  })

  await check('GET /orders/fake-id -> 404 (deep-link error case)', async () => {
    const res = await client.get('/orders/fake-id')
    assert.equal(res.status, 404)
  })

  await check('GET /tickets lists mocked tickets for /my-tickets', async () => {
    const res = await client.get('/tickets')
    assert.equal(res.status, 200)
    assert.ok(Array.isArray(res.data.data))
  })

  console.log(checks.join('\n'))
  server.close()
  process.exit(process.exitCode ?? 0)
}

main().catch((err) => {
  console.error(err)
  process.exit(1)
})
