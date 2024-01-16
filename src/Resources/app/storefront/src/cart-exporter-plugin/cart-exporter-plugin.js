import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
export default class CartExporterPlugin extends Plugin {
    init() {
        this._apiRoute = '/strack-integrations/export-cart';
        // this._apiRoute = '/example';
        this._client = new HttpClient();
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('click', this._onClick.bind(this));
    }

    _onClick(event) {
        event.preventDefault();
        console.log('Cart Export Clicked');
        this._fetch();
    }

    _fetch() {
        const type = this.el.getAttribute("data-strack-cart-export-type");
        const path = `${this._apiRoute}?type=${type}`;
        this.request_start = new Date();

        fetch(path)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.blob();
            })
            .then(blob => {
                const time = new Date() - this.request_start;
                console.log(`Took ${time}ms to get response with status 200`);

                this._downloadFile(blob, type);
            })
            .catch(error => {
                console.error(`Error: ${error.message}`);
            });
    }

    _downloadFile(blob, type) {
        const extension = (type === 'csv') ? 'csv' : 'xlsx';
        const fileName = `cart.${extension}`;

        const aElement = document.createElement('a');
        aElement.href = window.URL.createObjectURL(blob);
        aElement.setAttribute("download", fileName);
        aElement.setAttribute("target", "_blank");
        aElement.click();
        URL.revokeObjectURL(aElement.href);
    }

}