// paypal.js
const PayPalIntegration = {
    // Inicializar PayPal con tu Client ID
    init: function(clientId) {
        paypal.Buttons({
            createOrder: (data, actions) => {
                // Obtener items del carrito
                const cartItems = Cart.getItems();
                
                // Calcular total
                const total = cartItems.reduce((sum, item) => 
                    sum + (parseFloat(item.price) * item.quantity), 0);

                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            currency_code: 'MXN',
                            value: total.toFixed(2)
                        },
                        description: 'Compra en Moda Pixel',
                        items: cartItems.map(item => ({
                            name: item.name,
                            unit_amount: {
                                currency_code: 'MXN',
                                value: item.price
                            },
                            quantity: item.quantity
                        }))
                    }]
                });
            },
            onApprove: async (data, actions) => {
                try {
                    // Capturar el pago
                    const order = await actions.order.capture();
                    
                    // Enviar información de la orden al backend
                    const response = await fetch('process_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            paypal_order_id: order.id,
                            cart_items: Cart.getItems(),
                            total: order.purchase_units[0].amount.value,
                            status: order.status,
                            customer: order.payer
                        })
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        // Limpiar carrito
                        Cart.clear();
                        
                        // Mostrar mensaje de éxito
                        this.showSuccessMessage();
                        
                        // Redireccionar a página de confirmación
                        setTimeout(() => {
                            window.location.href = 'confirmation.html?order=' + result.order_id;
                        }, 2000);
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    console.error('Error processing payment:', error);
                    this.showErrorMessage(error.message);
                }
            },
            onError: (err) => {
                console.error('PayPal Error:', err);
                this.showErrorMessage('Hubo un error al procesar el pago. Por favor intente nuevamente.');
            }
        }).render('#paypal-button-container');
    },

    showSuccessMessage: function() {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = '¡Pago procesado exitosamente! Redirigiendo...';
        document.getElementById('cart-content').prepend(alert);
    },

    showErrorMessage: function(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = message;
        document.getElementById('cart-content').prepend(alert);
    }
};

export default PayPalIntegration;