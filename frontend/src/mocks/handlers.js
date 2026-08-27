import { http, HttpResponse, delay } from 'msw'
import { db, authUser, seedDatabase } from './db.js'

seedDatabase()

const json = (data, status = 200) => HttpResponse.json(data, { status })

/**
 * Routes are registered with a leading `*` wildcard so they match whichever
 * origin axios resolves against — see the note above `handlers`.
 */

async function eventList({ request }) {
  await delay(120)
  const url = new URL(request.url)
  const search = url.searchParams.get('search') ?? url.searchParams.get('q') ?? ''
  const category = url.searchParams.get('category') ?? ''
  const filter = url.searchParams.get('filter') ?? ''
  const sort = url.searchParams.get('sort') ?? ''
  const limit = Number(url.searchParams.get('limit') ?? '50')

  let list = db.event.getAll()
  if (category) list = list.filter((e) => e.category.toLowerCase() === category.toLowerCase())
  if (search) {
    const q = search.toLowerCase()
    list = list.filter(
      (e) =>
        e.title.toLowerCase().includes(q) ||
        e.description.toLowerCase().includes(q) ||
        e.venue_name.toLowerCase().includes(q) ||
        e.category.toLowerCase().includes(q),
    )
  }

  // Same calendar windows as Event::scopeWithinWindow.
  const now = Date.now()
  if (filter === 'today') list = list.filter((e) => new Date(e.start_datetime).getTime() <= now + 86400000)
  else if (filter === 'week') list = list.filter((e) => new Date(e.start_datetime).getTime() <= now + 7 * 86400000)
  else if (filter === 'month') list = list.filter((e) => new Date(e.start_datetime).getTime() <= now + 31 * 86400000)

  if (sort || filter === 'popular') {
    list.sort((a, b) => new Date(a.start_datetime) - new Date(b.start_datetime))
  }
  return json({ data: list.slice(0, limit) })
}

async function listCategories() {
  await delay(80)
  return json({ data: db.category.getAll().map((c) => c.name) })
}

async function showOrder({ params }) {
  await delay(200)
  const order = db.order.findFirst({ where: { id: { equals: String(params.orderId) } } })
  if (!order) return json({ message: 'Order not found' }, 404)
  return json({ data: order })
}

async function listTickets() {
  await delay(120)
  return json({ data: db.ticket.getAll() })
}

export const handlers = [
  // Every route uses a leading `*` so the pattern matches whichever origin
  // axios resolves against (Vite dev server, backend base URL, or the
  // http://mock.local origin used by npm run check:mocks). `*/events`
  // consequently covers BOTH /events and /api/events spellings.
  //
  /* ------------------------------- events ------------------------------- */
  http.get('*/events', eventList),
  http.get('*/events/:eventId', async ({ params }) => {
    await delay(100)
    const event = db.event.findFirst({ where: { id: { equals: Number(params.eventId) } } })
    if (!event) return json({ message: 'Event not found.' }, 404)
    return json({ data: event })
  }),

  /* ------------------------------ categories ---------------------------- */
  http.get('*/categories', listCategories),

  /* -------------------------------- auth -------------------------------- */
  http.post('*/auth/login', async ({ request }) => {
    const body = await request.json()
    await delay(250)
    if (!body?.email || !body?.password) return json({ message: 'Invalid credentials.' }, 401)
    return json({
      data: { user: { ...authUser, email: body.email }, token: 'mock-session-token' },
    })
  }),
  http.get('*/auth/me', async () => json({ data: { user: authUser } })),
  http.post('*/auth/logout', async () => json({ data: { success: true } })),

  /* ------------------------------ newsletter ---------------------------- */
  http.post('*/newsletter/subscribe', async ({ request }) => {
    const body = await request.json()
    await delay(150)
    if (!body?.email || !/^\S+@\S+\.\S+$/.test(body.email)) {
      return json(
        { error: 'A valid email address is required.', message: 'A valid email address is required.' },
        422,
      )
    }
    return json({ message: 'Subscribed successfully.' }, 201)
  }),

  /* --------------------------- cart / checkout -------------------------- */
  http.post('*/cart/verify', async () => json({ data: { valid: true } })),
  http.post('*/checkout/create-payment-intent', async () => {
    await delay(300)
    const order = db.order.getAll()[0]
    return json({
      data: {
        orderId: order?.id ?? null,
        clientSecret: 'mock_client_secret',
        amount: Number(order?.totalPrice ?? '0'),
      },
    })
  }),

  /* -------------------------------- orders ------------------------------ */
  // UUID-keyed like Features\Checkout\Models\Order.
  http.get('*/orders/:orderId', showOrder),

  /* ----------------------------- my tickets ----------------------------- */
  http.get('*/my-tickets', listTickets),
  http.get('*/tickets', listTickets),
]
