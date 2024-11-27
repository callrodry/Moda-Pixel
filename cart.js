// Funciones para manejar el carrito de compras
const Cart = {
    // Obtener el carrito actual
    getCart: function() {
        const cart = localStorage.getItem('cart');
        return cart ? JSON.parse(cart) : [];
    },

    // Agregar un producto al carrito
    addToCart: function(product) {
        const cart = this.getCart();
        const existingItem = cart.find(item => item.id === product.id);

        if (existingItem) {
            existingItem.quantity = (existingItem.quantity || 1) + 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image_url: product.image_url,
                quantity: 1
            });
        }

        localStorage.setItem('cart', JSON.stringify(cart));
        this.updateCartCount();
        return cart;
    },

    // Remover un producto del carrito
    removeFromCart: function(productId) {
        let cart = this.getCart();
        cart = cart.filter(item => item.id !== productId);
        localStorage.setItem('cart', JSON.stringify(cart));
        this.updateCartCount();
        return cart;
    },

    // Actualizar la cantidad de un producto
    updateQuantity: function(productId, quantity) {
        let cart = this.getCart();
        const item = cart.find(item => item.id === productId);
        if (item) {
            item.quantity = parseInt(quantity);
            if (item.quantity <= 0) {
                return this.removeFromCart(productId);
            }
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        this.updateCartCount();
        return cart;
    },

    // Obtener el total del carrito
    getTotal: function() {
        const cart = this.getCart();
        return cart.reduce((total, item) => {
            return total + (parseFloat(item.price) * item.quantity);
        }, 0);
    },

    // Actualizar el contador del carrito en la UI
    updateCartCount: function() {
        const cart = this.getCart();
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            cartCountElement.style.display = count > 0 ? 'block' : 'none';
        }
    },

    // Limpiar el carrito
    clearCart: function() {
        localStorage.removeItem('cart');
        this.updateCartCount();
    }
};

// Actualizar contador del carrito cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    Cart.updateCartCount();
});