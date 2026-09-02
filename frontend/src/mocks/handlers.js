import { http, HttpResponse, delay } from 'msw'
import { db, authUser, seedDatabase } from './db.js'

seedDatabase()

const json = (data, status = 200) => HttpResponse.json(data, { status })
let currentAuthUser = authUser
let isAuthenticated = false

function mockUserForEmail(email) {
  if (email === 'venue.staff@example.com') {
    return {
      ...authUser,
      name: 'Tunde Adeyemi',
      email,
      roles: [{ id: 2, name: 'venue_staff' }],
    }
  }

  if (email === 'organizer@example.com') {
    return {
      ...authUser,
      name: 'Amina Yusuf',
      email,
      roles: [{ id: 3, name: 'organizer' }],
    }
  }

  return { ...authUser, email }
}

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
  http.get('*/sanctum/csrf-cookie', async () => new HttpResponse(null, { status: 204 })),
  http.post('*/auth/login', async ({ request }) => {
    const body = await request.json()
    await delay(250)
    if (!body?.email || !body?.password) return json({ message: 'Invalid credentials.' }, 401)
    currentAuthUser = mockUserForEmail(body.email)
    isAuthenticated = true
    return json({
      data: { user: currentAuthUser, token: 'mock-session-token' },
    })
  }),
  http.get('*/auth/me', async () => {
    if (!isAuthenticated) return json({ message: 'Unauthenticated.' }, 401)
    return json(currentAuthUser)
  }),
  http.post('*/auth/logout', async () => {
    currentAuthUser = authUser
    isAuthenticated = false
    return json({ data: { success: true } })
  }),
  http.get('*/organizers/:userId', async () => json({ data: null })),

  /* ------------------------------ check-in ------------------------------ */
  http.get('*/venue/check-ins/stats', async () => {
    await delay(100)
    return json({ data: { total: 150, checked_in: 35, remaining: 115, rate: 23.3 } })
  }),
  http.get('*/venue/check-ins/search', async ({ request }) => {
    await delay(100)
    const query = new URL(request.url).searchParams.get('q')?.toLowerCase() ?? ''
    const attendees = [
      { id: 'check-in-1', name: 'Amaka Obi', email: 'amaka@example.com', ticket_code: 'EI-TCK-1001', checked_in: true },
      { id: 'check-in-2', name: 'Tunde Adeyemi', email: 'tunde@example.com', ticket_code: 'EI-TCK-1002', checked_in: false },
    ];
    const results = attendees.filter((attendee) =>
      [attendee.name, attendee.email, attendee.ticket_code].some((value) => value.toLowerCase().includes(query))
    )
    return json({ results, total: results.length, query })
  }),
  http.get('*/venue/check-ins/history', async () => {
    await delay(100)
    return json({ data: [] })
  }),
  http.get('*/venue/check-ins/export', async () => {
    await delay(100)
    return new HttpResponse('ticket_code,attendee_name,status\nEI-TCK-1001,Amaka Obi,checked_in\n', {
      headers: { 'Content-Type': 'text/csv' },
    })
  }),

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
