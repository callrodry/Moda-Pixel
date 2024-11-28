// cart.js
const Cart = {
    addToCart: function(product) {
        try {
            let cart = this.getCart();
            
            // Verificar si el producto ya está en el carrito
            const existingItem = cart.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    ...product,
                    quantity: 1
                });
            }
            
            // Guardar en localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Actualizar contador del carrito
            this.updateCartCount();

            // Mostrar mensaje de confirmación
            const alertSuccess = document.createElement('div');
            alertSuccess.className = 'alert-success';
            alertSuccess.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #000;
                color: white;
                padding: 15px 25px;
                border-radius: 4px;
                z-index: 1000;
            `;
            alertSuccess.textContent = 'Producto agregado al carrito';
            document.body.appendChild(alertSuccess);

            // Remover el mensaje después de 2 segundos
            setTimeout(() => {
                alertSuccess.remove();
            }, 2000);

        } catch (error) {
            console.error('Error al agregar al carrito:', error);
            alert('Error al agregar el producto al carrito');
        }
    },

    getCart: function() {
        const cart = localStorage.getItem('cart');
        return cart ? JSON.parse(cart) : [];
    },

    updateCartCount: function() {
        const cart = this.getCart();
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        const countElement = document.getElementById('cart-count');
        if (countElement) {
            countElement.textContent = count;
            countElement.style.display = count > 0 ? 'inline' : 'none';
        }
    },

    removeFromCart: function(productId) {
        let cart = this.getCart();
        cart = cart.filter(item => item.id !== productId);
        localStorage.setItem('cart', JSON_stringify(cart));
        this.updateCartCount();
    },

    updateQuantity: function(productId, quantity) {
        let cart = this.getCart();
        const item = cart.find(item => item.id === productId);
        if (item) {
            if (quantity > 0) {
                item.quantity = parseInt(quantity);
            } else {
                cart = cart.filter(item => item.id !== productId);
            }
            localStorage.setItem('cart', JSON.stringify(cart));
            this.updateCartCount();
        }
    },

    getTotal: function() {
        const cart = this.getCart();
        return cart.reduce((total, item) => total + (parseFloat(item.price) * item.quantity), 0);
    },

    clearCart: function() {
        localStorage.removeItem('cart');
        this.updateCartCount();
    }
};

// Hacer Cart disponible globalmente
window.Cart = Cart;

// Actualizar contador cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    Cart.updateCartCount();
});