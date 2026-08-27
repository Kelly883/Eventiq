import { factory, primaryKey } from '@mswjs/data'

/**
 * In-memory mock database modelled on the REAL backend resource shapes:
 *
 * - events  -> App\Http\Resources\EventPublicResource
 *              (id, title, description, start_datetime, venue_name,
 *               category, banner_image_url, ticket_price, tickets_remaining)
 * - categories -> Public\EventController::categories() returns a plain array
 *                 of distinct event `category` STRING values (not objects).
 * - orders  -> Features\Checkout\Models\Order (UUID string PK) + OrderResource
 * - tickets -> MyTicketsController::index
 *
 * Keeping these shapes here means a mocked response is byte-compatible with
 * the real one, so swapping MSW off later must not require touching UI code.
 */

let nextId = 1000
const uuid = () =>
  `ord-${Math.random().toString(16).slice(2, 8)}-${Date.now().toString(16)}-${nextId++}`

export const db = factory({
  event: {
    id: primaryKey(Number),
    title: String,
    description: String,
    start_datetime: String,
    end_datetime: String,
    venue_name: String,
    venue_address: String,
    category: String,
    banner_image_url: String,
    ticket_price: Number,
    tickets_remaining: Number,
    organizer: () => ({
      id: 1,
      name: 'Naija Live Sound',
      brandName: 'Naija Live',
      publicUrl: '/o/1',
    }),
  },

  category: {
    // Mirrors the string-only contract of GET /categories.
    id: primaryKey(String),
    name: String,
  },

  order: {
    id: primaryKey(String),
    userId: Number,
    status: String,
    totalPrice: String,
    ticketCount: Number,
    createdAt: String,
    items: Array,
  },

  ticket: {
    id: primaryKey(String),
    status: String,
    code: String,
    created_at: String,
    orderId: String,
    eventId: Number,
    event_name: String,
  },
})

export const authUser = {
  id: 1,
  name: 'Amaka Obi',
  email: 'buyer@example.com',
  roles: [{ id: 1, name: 'user' }],
}

function hours(n) {
  return new Date(Date.now() + n * 60 * 60 * 1000).toISOString()
}

/**
 * Seed only when empty so HMR re-evaluations keep the same state instead of
 * silently resetting what an in-flight test just mutated.
 */
export function seedDatabase() {
  if (db.event.count() > 0) return

  const rows = [
    ['Afrobeats Night Live', 'concerts', 'Eko Convention Centre', 4],
    ['Lagos Comedy Roast', 'comedy', 'Muson Centre', 30],
    ['Sunrise Gospel Festival', 'festivals', 'Tafawa Balewa Square', 96],
    ['Tech Summit Lagos', 'conferences', 'Landmark Event Centre', 168],
    ['Premier League Watch Party', 'sports', 'Raddison Blu Court', 20],
    ['National Theatre Season', 'theatre', 'National Arts Theatre', 240],
  ]

  let id = 1
  for (const [title, category, venue, inHours] of rows) {
    db.event.create({
      id: id++,
      title,
      category,
      description: `${title} — live in ${venue}.`,
      start_datetime: hours(inHours),
      end_datetime: hours(inHours + 3),
      venue_name: venue,
      venue_address: `${venue}, Lagos`,
      banner_image_url: '',
      ticket_price: 5000 + id * 500,
      tickets_remaining: id % 2 === 0 ? Math.max(1, 24 - id) : 120,
    })
  }

  // GET /categories returns plain strings — modelled one-per-value.
  for (const name of [...new Set(db.event.getAll().map((e) => e.category))]) {
    db.category.create({ id: name, name })
  }

  // One purchasable order owned by the mock user (uuid PK like the backend).
  db.order.create({
    id: uuid(),
    userId: authUser.id,
    status: 'confirmed',
    totalPrice: '25000.00',
    ticketCount: 2,
    createdAt: new Date().toISOString(),
    items: [{ tier: 'General Admission', quantity: 2, unitPrice: '12500.00' }],
  })

  db.ticket.create({
    id: 'tk-1001',
    status: 'active',
    code: 'EI-TCK-1001',
    created_at: new Date().toISOString(),
    orderId: db.order.getAll()[0]?.id ?? '',
    eventId: 1,
    event_name: 'Afrobeats Night Live',
  })
}
