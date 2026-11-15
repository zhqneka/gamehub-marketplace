
import { useParams, Link } from 'react-router-dom'
import { useEffect, useState } from 'react'

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

type ProductDetailsProps = {
  onAddToCart: (product: Product, offer: Offer) => void
}

function ProductDetails({ onAddToCart }: ProductDetailsProps) {
  const { id } = useParams()
  const [product, setProduct] = useState<Product | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    fetch(`http://localhost:8000/api/products/${id}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error('Product not found')
        }
        return response.json()
      })
      .then((data) => {
        setProduct(data.product)
      })
      .catch((err) => {
        setError(err.message)
      })
      .finally(() => {
        setLoading(false)
      })
  }, [id])

  if (loading) {
    return <h2>Loading product...</h2>
  }

  if (error || !product) {
    return (
      <div className="product-page">
        <h2>{error || 'Product not found'}</h2>
        <Link to="/">← Back to Store</Link>
      </div>
    )
  }

  return (
    <div className="product-page">
      <Link to="/">← Back to Store</Link>

      <div className="product-page-content">
        <div className="product-page-image">
          {product.image_url && (
            <img
              src={product.image_url}
              alt={product.name}
              className="details-image"
            />
          )}
        </div>

        <div className="product-page-info">
          <h1>{product.name}</h1>
          <p>{product.description}</p>

          <div className="product-page-meta">
            <p>Platform: {product.platform}</p>
            <p>Region: {product.region}</p>
          </div>

          <h2>Available offers</h2>

          {product.offers.map((offer) => (
            <div className="offer-info" key={offer.offer_id}>
              <h3>€{Number(offer.price).toFixed(2)}</h3>
              <p>Seller: {offer.seller_name}</p>
              <p>Rating: ★ {Number(offer.seller_rating).toFixed(1)}</p>
              <p>Stock: {offer.stock}</p>

              <button
                className="buy-button"
                disabled={offer.stock <= 0}
                onClick={() => onAddToCart(product, offer)}
              >
                {offer.stock > 0 ? 'Buy Now' : 'Out of stock'}
              </button>
            </div>
          ))}


          {product.offers.length === 0 && (
            <p>No offers available for this game.</p>
          )}
        </div>
      </div>
    </div>
  )
}

export default ProductDetails
