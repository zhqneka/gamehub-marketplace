import { useEffect, useState } from 'react'
import { useNavigate, Routes, Route } from 'react-router-dom'
import ProductDetails from './components/ProductDetails'
import './App.css'

type Offer = {
  offer_id: number
  price: number
  stock: number
  seller_name: string
  seller_rating: number
}

type Product = {
  id: number
  name: string
  description: string | null
  platform: string | null
  region: string | null
  image_url: string | null
  offers: Offer[]
}

type CartItem = {
  product: Product
  offer: Offer
  quantity: number
}

type User = {
  id: number
  name: string
  email: string
}

function App() {
  const navigate = useNavigate()

  const [products, setProducts] = useState<Product[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [selectedPlatform, setSelectedPlatform] = useState('')
  const [selectedRegion, setSelectedRegion] = useState('')
  const [cart, setCart] = useState<CartItem[]>([])
  const [token, setToken] = useState<string | null>(
    () => localStorage.getItem('auth_token')
  )
  const [user, setUser] = useState<User | null>(null)
  const [authLoading, setAuthLoading] = useState(true)

  useEffect(() => {
    fetch('http://localhost:8000/api/products')
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to load products')
        }
        return response.json()
      })
      .then((data) => {
        setProducts(data.products)
      })
      .catch((err) => {
        setError(err.message)
      })
      .finally(() => {
        setLoading(false)
      })
  }, [])

  
  useEffect(() => {
    const verifyToken = async () => {
      const savedToken = localStorage.getItem('auth_token')

      if (!savedToken) {
        setToken(null)
        setUser(null)
        setAuthLoading(false)
        return
      }

      try {
        const response = await fetch(
          'http://localhost:8000/api/auth/me',
          {
            headers: {
              Authorization: `Bearer ${savedToken}`,
              Accept: 'application/json',
            },
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error || 'Session expired')
        }

        setToken(savedToken)
        setUser(data.user)
      } catch {
        localStorage.removeItem('auth_token')
        setToken(null)
        setUser(null)
      } finally {
        setAuthLoading(false)
      }
    }

    verifyToken()
  }, [])

  const addToCart = (product: Product, offer: Offer) => {
    setCart((currentCart) => {
      const existingItem = currentCart.find(
        (item) => item.offer.offer_id === offer.offer_id
      )

      if (existingItem) {
        return currentCart.map((item) =>
          item.offer.offer_id === offer.offer_id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        )
      }

      return [...currentCart, { product, offer, quantity: 1 }]
    })

    alert('Product added to cart!')
  }

  const removeFromCart = (offerId: number) => {
    setCart((currentCart) =>
      currentCart.filter((item) => item.offer.offer_id !== offerId)
    )
  }

  const clearCart = () => {
    setCart([])
  }

  const cartCount = cart.reduce(
    (total, item) => total + item.quantity,
    0
  )
  
  const filteredProducts = products.filter((product) => {
    const matchesSearch = product.name
      .toLowerCase()
      .includes(search.toLowerCase())

    const matchesPlatform =
      selectedPlatform === '' ||
      product.platform === selectedPlatform

    const matchesRegion =
      selectedRegion === '' ||
      product.region === selectedRegion

    return matchesSearch && matchesPlatform && matchesRegion
  })
  if (authLoading) {
    return (
      <main className="marketplace">
        <p>Loading...</p>
      </main>
    )
  }

  if (loading) {
    return <h2 className="status-message">Loading products...</h2>
  }

  if (error) {
    return <h2 className="status-message">Error: {error}</h2>
  }
  const handleAuthSuccess = (newToken: string, loggedInUser: User) => {
    localStorage.setItem('auth_token', newToken)
    setToken(newToken)
    setUser(loggedInUser)
    navigate('/')
  }
  
  const handleLogout = async () => {
    if (token) {
      try {
        await fetch('http://localhost:8000/api/auth/logout', {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
      } catch {
        // Clear the local session even if the server is unavailable.
      }
    }
  
    localStorage.removeItem('auth_token')
    setToken(null)
    setUser(null)
    navigate('/')
  }

  return (
    <Routes>
      <Route
        path="/"
        element={
          <main className="marketplace">
            <nav className="navbar">
              <div className="brand">
                GAME<span>HUB</span>
              </div>

              <div className="nav-links">
                <a href="#products">Store</a>
                <a href="#products">Games</a>
              </div>

              <div className="nav-actions">
                <button
                  className="cart-button"
                  onClick={() => navigate('/cart')}
                >
                  🛒 Cart ({cartCount})
                </button>
               
                  {user ? (
                    <>
                      <span>Hi, {user.name}</span>

                      <button
                        className="login-button"
                        onClick={() => navigate('/orders')}
                      >
                        My Orders
                      </button>

                      <button
                        className="login-button logout-button"
                        onClick={handleLogout}
                      >
                        Log out
                      </button>
                    </>
                  ) : (
                    <button
                      className="login-button"
                      onClick={() => navigate('/login')}
                    >
                      Log in
                    </button>
                  )}
                </div>
                </nav>
            <div className="search-container">
              <input
                type="text"
                placeholder="Search for a game..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />

              <div className="filters">
                <select
                  value={selectedPlatform}
                  onChange={(e) => setSelectedPlatform(e.target.value)}
                >
                  <option value="">Select platform</option>
                  <option value="PC">PC</option>
                  <option value="PlayStation">PlayStation</option>
                  <option value="Xbox">Xbox</option>
                </select>

                <select
                  value={selectedRegion}
                  onChange={(e) => setSelectedRegion(e.target.value)}
                >
                  <option value="">Select region</option>
                  <option value="Global">Global</option>
                  <option value="Europe">Europe</option>
                  <option value="North America">North America</option>
                </select>
              </div>
            </div>

            <section className="products-section" id="products">
              <h2>Popular games</h2>

              <div className="products-grid">
                {filteredProducts.map((product) => {
                  const offers = [...(product.offers || [])].sort(
                    (a, b) => Number(a.price) - Number(b.price)
                  )

                  const offer = offers[0]

                  return (
                    <div
                      className="product-card"
                      key={product.id}
                      onClick={() => navigate(`/product/${product.id}`)}
                    >
                      {product.image_url ? (
                        <img
                          src={product.image_url}
                          alt={product.name}
                          className="product-image"
                        />
                      ) : (
                        <div className="image-placeholder">
                          {product.name}
                        </div>
                      )}

                      <div className="product-info">
                        <h3>{product.name}</h3>

                        <p className="description">
                          {product.description}
                        </p>

                        <div className="product-details">
                          <span>
                            {product.platform || 'Platform not specified'}
                          </span>
                          <span>
                            {product.region || 'Region not specified'}
                          </span>
                        </div>

                        {offer ? (
                          <div className="offer-info">
                            <p className="price">
                              €{Number(offer.price).toFixed(2)}
                            </p>

                            <p className="seller">
                              Sold by {offer.seller_name}
                            </p>

                            <div className="seller-details">
                              <span>
                                ★ {Number(offer.seller_rating).toFixed(1)}
                              </span>
                              <span>{offer.stock} in stock</span>
                            </div>

                            <button
                              className="buy-button"
                              type="button"
                              onClick={(e) => {
                                e.stopPropagation()
                                navigate(`/product/${product.id}`)
                              }}
                            >
                              View offer
                            </button>
                          </div>
                        ) : (
                          <p className="unavailable">
                            Currently unavailable
                          </p>
                        )}
                      </div>
                    </div>
                  )
                })}
              </div>

              {filteredProducts.length === 0 && (
                <p>No products found. Try another search.</p>
              )}
            </section>
          </main>
        }
      />
      <Route
        path="/login"
        element={
          <AuthPage
            mode="login"
            onAuthSuccess={handleAuthSuccess}
          />
        }
      />

      <Route
        path="/register"
        element={
          <AuthPage
            mode="register"
            onAuthSuccess={handleAuthSuccess}
          />
        }
      />
      <Route
        path="/product/:id"
        element={<ProductDetails onAddToCart={addToCart} />}
      />

      <Route
        path="/cart"
        element={
          <CartPage
            cart={cart}
            onRemove={removeFromCart}
          />
        }
      />

      <Route
        path="/orders"
        element={<OrderHistoryPage token={token} />}
      />

      <Route
        path="/checkout"
        element={
          <CheckoutPage
            cart={cart}
            onPaymentSuccess={clearCart}
            token={token}
            user={user}
          />
        }
      />
    </Routes>
  )
}

function CartPage({
  cart,
  onRemove,
}: {
  cart: CartItem[]
  onRemove: (offerId: number) => void
}) {
  const navigate = useNavigate()

  const total = cart.reduce(
    (sum, item) => sum + Number(item.offer.price) * item.quantity,
    0
  )

  return (
    <main className="marketplace">
      <div className="product-page">
        <h1>Shopping Cart</h1>

        <button
          className="cart-button"
          onClick={() => navigate('/')}
        >
          ← Continue shopping
        </button>

        {cart.length === 0 ? (
          <p>Your cart is empty.</p>
        ) : (
          <>
            {cart.map((item) => (
              <div className="offer-info" key={item.offer.offer_id}>
                <h3>{item.product.name}</h3>
                <p>Seller: {item.offer.seller_name}</p>
                <p>
                  Price: €{Number(item.offer.price).toFixed(2)}
                </p>
                <p>Quantity: {item.quantity}</p>
                <p>
                  Subtotal: €
                  {(Number(item.offer.price) * item.quantity).toFixed(2)}
                </p>

                <button
                  onClick={() => onRemove(item.offer.offer_id)}
                >
                  Remove
                </button>
              </div>
            ))}

            <h2>Total: €{total.toFixed(2)}</h2>

            <button
              className="buy-button"
              onClick={() => navigate('/checkout')}
            >
              Proceed to Checkout
            </button>
          </>
        )}
      </div>
    </main>
  )
}


function CheckoutPage({
  cart,
  onPaymentSuccess,
  token,
  user,
}: {
  cart: CartItem[]
  onPaymentSuccess: () => void
  token: string | null
  user: {
    id: number
    name: string
    email: string
  } | null
}) {
  const navigate = useNavigate()

  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  const [order, setOrder] = useState<{
    id: number
    email: string
    total: number
    status: string
    payment_status: string
    payment_reference: string
    delivery_status: string
    digital_keys: {
      offer_id: number
      key: string
    }[]
  } | null>(null)

  const total = cart.reduce(
    (sum, item) =>
      sum + Number(item.offer.price) * item.quantity,
    0
  )

  const handlePayment = async (
    e: React.FormEvent<HTMLFormElement>
  ) => {
    e.preventDefault()

    if (!token || !user) {
      navigate('/login')
      return
    }

    setSubmitting(true)
    setError('')

    try {
      const response = await fetch(
        'http://localhost:8000/api/orders',
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            email: user?.email,
            items: cart.map((item) => ({
              offer_id: item.offer.offer_id,
              quantity: item.quantity,
            })),
          }),
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.error || 'Payment failed.')
      }

      setOrder(data.order)
      onPaymentSuccess()
    } catch (err) {
      setError(
        err instanceof Error
          ? err.message
          : 'Something went wrong.'
      )
    } finally {
      setSubmitting(false)
    }
  }

  if (order) {
    return (
      <main className="marketplace">
        <div className="product-page">
          <h1>Payment successful!</h1>

          <p>Your order has been created successfully.</p>

          <h2>Order #{order.id}</h2>

          <p>
            Email: <strong>{order.email}</strong>
          </p>

          <p>
            Total: €{Number(order.total).toFixed(2)}
          </p>

          <p>
            Payment status: <strong>{order.payment_status}</strong>
          </p>

          <p>
            Payment reference: {order.payment_reference}
          </p>

          <p>
            Digital delivery: <strong>{order.delivery_status}</strong>
          </p>

          {order.digital_keys.length > 0 && (
            <>
              <h2>Your digital keys</h2>

              {order.digital_keys.map((item, index) => (
                <p key={`${item.offer_id}-${index}`}>
                  <strong>{item.key}</strong>
                </p>
              ))}
            </>
          )}

          {order.delivery_status === 'pending' && (
            <p>
              Your order is confirmed, but digital keys are not
              available for every item yet.
            </p>
          )}

          <p>
            <small>
              This is a simulated payment. No real money was charged.
            </small>
          </p>

          <button
            className="buy-button"
            onClick={() => navigate('/')}
          >
            Back to Store
          </button>
        </div>
      </main>
    )
  }

  if (cart.length === 0) {
    return (
      <main className="marketplace">
        <div className="product-page">
          <h1>Checkout</h1>
          <p>Your cart is empty.</p>

          <button
            className="buy-button"
            onClick={() => navigate('/')}
          >
            Return to Store
          </button>
        </div>
      </main>
    )
  }

  if (!token || !user) {
    return (
      <main className="marketplace">
        <div className="product-page">
          <h1>Login required</h1>
          <p>Please log in or create an account before checkout.</p>

          <button
            className="buy-button"
            onClick={() => navigate('/login')}
          >
            Log in
          </button>
        </div>
      </main>
    )
  }

  return (
    <main className="marketplace">
      <div className="product-page">
        <button
          className="cart-button"
          onClick={() => navigate('/cart')}
        >
          ← Back to Cart
        </button>

        <h1>Checkout</h1>

        <p>Review your order before payment.</p>

        <h2>Customer information</h2>
        <p>
          Name: <strong>{user.name}</strong>
        </p>
        <p>
          Email: <strong>{user.email}</strong>
        </p>

        <h2>Order summary</h2>

        {cart.map((item) => (
          <div
            className="offer-info"
            key={item.offer.offer_id}
          >
            <h3>{item.product.name}</h3>

            <p>Seller: {item.offer.seller_name}</p>

            <p>Quantity: {item.quantity}</p>

            <p>
              Subtotal: €
              {(
                Number(item.offer.price) * item.quantity
              ).toFixed(2)}
            </p>
          </div>
        ))}

        <h2>Total: €{total.toFixed(2)}</h2>

        <form onSubmit={handlePayment}>
          {error && (
            <p style={{ color: 'red' }}>{error}</p>
          )}

          <p>
            Payment method: <strong>Simulated payment</strong>
          </p>

          <button
            className="buy-button"
            type="submit"
            disabled={submitting}
          >
            {submitting
              ? 'Processing...'
              : `Pay €${total.toFixed(2)}`}
          </button>
        </form>
      </div>
    </main>
  )
}


type Order = {
  id: number
  customer_email: string
  total: number
  status: string
  payment_status: string
  payment_reference: string
  delivery_status: string
  created_at: string
  items: {
    id: number
    offer_id: number
    quantity: number
    unit_price: number
    digital_keys: string[]
  }[]
}


function OrderHistoryPage({
  token,
}: {
  token: string | null
}) {
  const navigate = useNavigate()

  const [orders, setOrders] = useState<Order[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [copiedKey, setCopiedKey] = useState('')

  useEffect(() => {
    if (!token) {
      setLoading(false)
      navigate('/login')
      return
    }

    const fetchOrders = async () => {
      try {
        const response = await fetch(
          'http://localhost:8000/api/orders',
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: 'application/json',
            },
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error || 'Could not load orders.')
        }

        setOrders(data.orders || [])
      } catch (err) {
        setError(
          err instanceof Error
            ? err.message
            : 'Something went wrong.'
        )
      } finally {
        setLoading(false)
      }
    }

    fetchOrders()
  }, [token, navigate])

  const copyKey = async (key: string) => {
    try {
      await navigator.clipboard.writeText(key)
      setCopiedKey(key)

      window.setTimeout(() => {
        setCopiedKey('')
      }, 2000)
    } catch {
      setError('Could not copy the key. Please copy it manually.')
    }
  }

  return (
    <main className="marketplace">
      <div className="orders-page">

        <button
          className="cart-button orders-back"
          onClick={() => navigate('/')}
        >
          ← Back to Store
        </button>

        <div className="orders-heading">
          <h1>My Orders</h1>
          <p>View your purchases and digital game keys.</p>
        </div>

        {loading && (
          <p className="orders-message">Loading your orders...</p>
        )}

        {error && (
          <p className="orders-error">{error}</p>
        )}

        {!loading && !error && orders.length === 0 && (
          <div className="empty-orders">
            <div className="empty-orders-icon">🛒</div>
            <h2>No orders yet</h2>
            <p>Your purchases will appear here.</p>

            <button
              className="buy-button"
              onClick={() => navigate('/')}
            >
              Browse Games
            </button>
          </div>
        )}

        {!loading && !error && orders.map((order) => (
          <div className="order-card" key={order.id}>

            <div className="order-card-header">
              <div>
                <span className="order-label">ORDER</span>
                <h2>#{order.id}</h2>
              </div>

              <span
                className={`status-badge ${
                  order.delivery_status === 'delivered'
                    ? 'status-delivered'
                    : 'status-pending'
                }`}
              >
                {order.delivery_status}
              </span>
            </div>

            <div className="order-summary">
              <div className="order-detail">
                <span>Date</span>
                <strong>
                  {new Date(order.created_at).toLocaleDateString()}
                </strong>
              </div>

              <div className="order-detail">
                <span>Total</span>
                <strong>
                  €{Number(order.total).toFixed(2)}
                </strong>
              </div>

              <div className="order-detail">
                <span>Payment</span>
                <strong className="payment-success">
                  {order.payment_status}
                </strong>
              </div>

              <div className="order-detail">
                <span>Delivery</span>
                <strong>{order.delivery_status}</strong>
              </div>
            </div>

            <div className="order-reference">
              Reference: {order.payment_reference}
            </div>

            <div className="purchased-items">
              <h3>Purchased items</h3>

              {order.items.map((item) => (
                <div className="purchased-item" key={item.id}>

                  <div className="purchased-item-details">
                    <strong>Offer #{item.offer_id}</strong>

                    <span>Quantity: {item.quantity}</span>

                    <span>
                      Unit price: €{Number(item.unit_price).toFixed(2)}
                    </span>
                  </div>

                  {item.digital_keys.length > 0 && (
                    <div className="digital-key-section">
                      <div className="digital-key-heading">
                        <span>🔑 Digital keys</span>
                        <span className="key-ready">Ready to use</span>
                      </div>

                      {item.digital_keys.map((key, index) => (
                        <div className="digital-key" key={index}>
                          <strong>{key}</strong>

                          <button
                            className="copy-key-button"
                            onClick={() => copyKey(key)}
                          >
                            {copiedKey === key ? '✓ Copied' : 'Copy key'}
                          </button>
                        </div>
                      ))}
                    </div>
                  )}

                  {item.digital_keys.length === 0 && (
                    <div className="key-pending">
                      Digital key is not available yet.
                    </div>
                  )}

                </div>
              ))}
            </div>

          </div>
        ))}

      </div>
    </main>
  )
}

function AuthPage({
  mode,
  onAuthSuccess,
}: {
  mode: 'login' | 'register'
  onAuthSuccess: (token: string, user: User) => void
}) {
  const navigate = useNavigate()

  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const isRegister = mode === 'register'

  const handleSubmit = async (
    e: React.FormEvent<HTMLFormElement>
  ) => {
    e.preventDefault()
    setSubmitting(true)
    setError('')

    try {
      const response = await fetch(
        `http://localhost:8000/api/auth/${mode}`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify(
            isRegister
              ? { name, email, password }
              : { email, password }
          ),
        }
      )

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.error || 'Authentication failed.')
      }

      onAuthSuccess(data.token, data.user)
    } catch (err) {
      setError(
        err instanceof Error
          ? err.message
          : 'Something went wrong.'
      )
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="auth-page">
      <div className="auth-card">

        <button
          className="cart-button"
          onClick={() => navigate('/')}
        >
          ← Back to Store
        </button>

        <div className="auth-heading">
          <h1>
            {isRegister ? 'Create an account' : 'Welcome back'}
          </h1>

          <p>
            {isRegister
              ? 'Join Eneba and discover great gaming deals.'
              : 'Log in to continue to your Eneba account.'}
          </p>
        </div>

        <form onSubmit={handleSubmit}>

          {isRegister && (
            <>
              <label htmlFor="auth-name">Full name</label>
              <input
                id="auth-name"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                maxLength={100}
                placeholder="Enter your full name"
              />
            </>
          )}

          <label htmlFor="auth-email">Email address</label>
          <input
            id="auth-email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            placeholder="you@example.com"
          />

          <label htmlFor="auth-password">Password</label>
          <input
            id="auth-password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            minLength={isRegister ? 8 : undefined}
            placeholder="Enter your password"
          />

          {error && (
            <p className="auth-error">{error}</p>
          )}

          <button
            className="auth-submit"
            type="submit"
            disabled={submitting}
          >
            {submitting
              ? 'Please wait...'
              : isRegister
                ? 'Create account'
                : 'Log in'}
          </button>

        </form>

        <div className="auth-switch">
          {isRegister
            ? 'Already have an account? '
            : "Don't have an account? "}

          <button
            type="button"
            onClick={() =>
              navigate(isRegister ? '/login' : '/register')
            }
          >
            {isRegister ? 'Log in' : 'Register'}
          </button>
        </div>

      </div>
    </main>
  )
}

export default App