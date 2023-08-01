import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class QuantitySelectorCustomerPricePlugin extends Plugin {

    init() {
        this.quantityInput = DomAccess.querySelector(this.el.parentNode, '[data-quantity-selector] input', false);
        if (!this.quantityInput) {
            return;
        }

        this.productId = this.el.getAttribute('data-product-id');
        this.customerPriceEndpoint = this.el.getAttribute('data-customer-price-endpoint');
        this.isComponent = this.el.getAttribute('data-is-component') === '1';
        this.buyWidgetPriceContainer = DomAccess.querySelector(document, '.product-detail-price-container');
        this.client = new HttpClient(window.accessKey, window.contextToken);
        this.registerEventListeners();
    }

    registerEventListeners() {
        this.quantityInput.addEventListener('change', this.onQuantityChange.bind(this));
    }

    onQuantityChange() {
        this.createLoadingIndicator();
        this.client.post(
            this.customerPriceEndpoint,
            JSON.stringify({productId: this.productId, quantity: this.quantityInput.value, isComponent: this.isComponent}),
            response => {
                this.replaceCustomerPrice(response);
                this.removeLoadingIndicator();
            }
        );
    }

    replaceCustomerPrice(buyWidgetPrice) {
        this.buyWidgetPriceContainer.innerHTML = buyWidgetPrice;
    }

    createLoadingIndicator() {
        ElementLoadingIndicatorUtil.create(this.el.parentNode);
    }

    removeLoadingIndicator() {
        ElementLoadingIndicatorUtil.remove(this.el.parentNode);
    }

}
