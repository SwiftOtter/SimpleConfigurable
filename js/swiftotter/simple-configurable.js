/**************************** CONFIGURABLE PRODUCT **************************/
Product.SimpleConfig = Class.create();
Product.SimpleConfig.prototype = {
    initialize: function(config){
        this.config     = config;
        this.taxConfig  = this.config.taxConfig;
        this.settings   = $$('.super-attribute-select');
        this.state      = new Hash();
        this.priceTemplate = new Template(this.config.template);
        this.prices     = config.prices;

        if (window["optionsPrice"] !== undefined) {
            optionsPrice.productPrice = this.config.basePrice;
            optionsPrice.productOldPrice = this.config.oldPrice;
        }

        this.settings.each(function(element){
            Event.observe(element, 'change', this.configure.bind(this))
        }.bind(this));

        // fill state
        this.settings.each(function(element){
            var attributeId = element.id.replace(/[a-z]*/, '');
            if(attributeId && this.config.attributes[attributeId]) {
                element.config = this.config.attributes[attributeId];
                element.attributeId = attributeId;
                this.state[attributeId] = false;
            }
        }.bind(this))

        // Init settings dropdown
        var childSettings = [];
        for(var i=this.settings.length-1;i>=0;i--){
            var prevSetting = this.settings[i-1] ? this.settings[i-1] : false;
            var nextSetting = this.settings[i+1] ? this.settings[i+1] : false;
            if(i==0){
                this.fillSelect(this.settings[i])
            }
            else {
                this.settings[i].disabled=true;
            }
            $(this.settings[i]).childSettings = childSettings.clone();
            $(this.settings[i]).prevSetting   = prevSetting;
            $(this.settings[i]).nextSetting   = nextSetting;
            childSettings.push(this.settings[i]);
        }

        // Set default values - from config and overwrite them by url values
        if (config.defaultValues) {
            this.values = config.defaultValues;
        }

        var separatorIndex = window.location.href.indexOf('#');
        if (separatorIndex != -1) {
            var paramsStr = window.location.href.substr(separatorIndex+1);
            var urlValues = paramsStr.toQueryParams();
            if (!this.values) {
                this.values = {};
            }
            for (var i in urlValues) {
                this.values[i] = urlValues[i];
            }
        }

        this.configureForValues();
        document.observe("dom:loaded", this.configureForValues.bind(this));
    },

    configureForValues: function () {
        if (this.values) {
            this.settings.each(function(element){
                var attributeId = element.attributeId;
                element.value = (typeof(this.values[attributeId]) == 'undefined')? '' : this.values[attributeId];
                this.configureElement(element);
            }.bind(this));
        }

        if (window["optionsPrice"] !== undefined) {
            this.reloadPrice();
        }
    },

    configure: function(event){
        var element = Event.element(event);
        this.configureElement(element);
    },

    configureElement : function(element) {
        this.reloadOptionLabels(element);
        if(element.value){
            this.state[element.config.id] = element.value;
            if(element.nextSetting){
                element.nextSetting.disabled = false;
                this.fillSelect(element.nextSetting);
                this.resetChildren(element.nextSetting);
            }
        }
        else {
            this.resetChildren(element);
        }
        this.reloadPrice();
//      Calculator.updatePrice();
    },

    reloadProduct: function() {
        var selectedOptions = $H(this.getSelectedOptions()),
            productIds = {};

        selectedOptions.each(function(pair) {
            var option = pair.value;
            if (option.products !== undefined) {
                for (var productIterator = 0; productIterator < option.products.length; productIterator++) {
                    var productInfo = option.products[productIterator],
                        productId = productInfo.id;

                    if (productIds[productId] !== undefined) {
                        var value = parseInt(productIds[productId].count);
                        productIds[productId].count = value+1;
                    } else {
                        productInfo.count = 1;
                        productIds[productId] = productInfo;
                    }
                }
            }
        });

        var maxProductId = 0,
            maxProductHitCount = 0,
            productInfo = {};

        $H(productIds).each(function(pair) {
            var productId = pair.key,
                productPrice = pair.value.price,
                hitCount = pair.value.count;

            if (hitCount > maxProductHitCount) {
                maxProductHitCount = hitCount;
                maxProductId = productId;
                productInfo = pair.value;
            }
        });

        if (maxProductId === 0) {
            this.config.product = undefined;
            this.config.productPrice = undefined;
            return false;
        } else {
            this.config.product = maxProductId;
            this.config.productPrice = productInfo.price;
            this.config.productOldPrice = productInfo.old_price;
            this.config.productStock = productInfo.stock;
            return maxProductId;
        }

//        for (; i < this.settings.length; i++) {
//
//        }


    },

    getSelectedOptions: function() {
        var options = {};
        for(var i=this.settings.length-1;i>=0;i--){
            var selected = this.settings[i].options[this.settings[i].selectedIndex];
            if(selected.config){
                options[selected.config.id] = selected.config;
            }
        }

        return options;
    },

    reloadOptionLabels: function(element){
        var selectedPrice;
        if(element.options[element.selectedIndex].config){
            selectedPrice = parseFloat(element.options[element.selectedIndex].config.price)
        }
        else{
            selectedPrice = 0;
        }
        for(var i=0;i<element.options.length;i++){
            if(element.options[i].config){
                var price = element.options[i].config.price,
                    priceRange = "";

                if (this.config.showPrices && element.options[i].config.allowedProducts) {
                    var result = this.getPriceOrRange(element.options[i].config.allowedProducts, price);
                    price = result.price;
                    priceRange = result.priceRange;
                }

                element.options[i].text = this.getOptionLabel(element.options[i].config, price, priceRange);
            }
        }
    },

    resetChildren : function(element){
        if(element.childSettings) {
            for(var i=0;i<element.childSettings.length;i++){
                element.childSettings[i].selectedIndex = 0;
                element.childSettings[i].disabled = true;
                if(element.config){
                    this.state[element.config.id] = false;
                }
            }
        }
    },

    fillSelect: function(element){
        var attributeId = element.id.replace(/[a-z]*/, '');
        var options = this.getAttributeOptions(attributeId);
        this.clearSelect(element);
        element.options[0] = new Option(this.config.chooseText, '');

        var prevConfig = false;
        if(element.prevSetting){
            prevConfig = element.prevSetting.options[element.prevSetting.selectedIndex];
        }

        if(options) {
            var index = 1;
            for(var i=0;i<options.length;i++){
                var allowedProducts = [];
                if(prevConfig) {
                    for(var j=0;j<options[i].products.length;j++){
                        if (prevConfig.config.allowedProducts) {
                            for (var p=0; p<prevConfig.config.allowedProducts.length; p++) {
                                var configItem = prevConfig.config.allowedProducts[p];
                                if (configItem !== undefined && options[i].products[j].id == configItem.id) {
                                    allowedProducts.push(options[i].products[j]);
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    allowedProducts = options[i].products.clone();
                }

                if(allowedProducts.size()>0){
                    options[i].allowedProducts = allowedProducts;

                    var price = 0,
                        priceRange = "",
                        result = this.getPriceOrRange(allowedProducts, options[i].price);

                    price = result.price || 0;

                    if (this.config.showPrices) {
                        priceRange = result.priceRange || "";
                    }

                    element.options[index] = new Option(this.getOptionLabel(options[i], price, priceRange), options[i].id);
                    element.options[index].config = options[i];

                    $(element.options[index]).setAttribute('price', price);

                    index++;
                }
            }
        }
    },

    getPriceOrRange: function(allowedProducts, defaultPrice) {
        var price, priceRange;

        if (allowedProducts.length === 1) {
            price = allowedProducts[0].price;
        } else {
            price = defaultPrice || 0;

            var min = Number.MAX_VALUE, max = 0;
            for (var num = 0; num < allowedProducts.length; num++) {
                min = Math.min(min, allowedProducts[num].price);
                max = Math.max(max, allowedProducts[num].price);
            }

            if (min < Number.MAX_VALUE && max > 0) {
                priceRange = ' (' + this.formatPrice(min) + ' - ' + this.formatPrice(max) + ')';
            }
        }

        return { price: price, priceRange: priceRange };
    },

    getOptionLabel: function(option, price, priceRange){
        var price = parseFloat(price);
        if (this.taxConfig.includeTax) {
            var tax = price / (100 + this.taxConfig.defaultTax) * this.taxConfig.defaultTax;
            var excl = price - tax;
            var incl = excl*(1+(this.taxConfig.currentTax/100));
        } else {
            var tax = price * (this.taxConfig.currentTax / 100);
            var excl = price;
            var incl = excl + tax;
        }

        if (this.taxConfig.showIncludeTax || this.taxConfig.showBothPrices) {
            price = incl;
        } else {
            price = excl;
        }

        var str = option.label;
        if(price && (this.settings.length === 1 || this.config.showPrices)){
            if (this.taxConfig.showBothPrices) {
                str+= ' ' + this.formatPrice(excl, false) + ' (' + this.formatPrice(price, false) + ' ' + this.taxConfig.inclTaxTitle + ')';
            } else {
                str+= ' ' + this.formatPrice(price, false);
            }
        }

        if (this.config.showPrices && priceRange !== undefined) {
            str += priceRange;
        }

        return str;
    },

    formatPrice: function(price, showSign){
        var str = '';
        price = parseFloat(price);
        if(showSign){
            if(price<0){
                str+= '-';
                price = -price;
            }
            else{
                str+= '+';
            }
        }

        var roundedPrice = (Math.round(price*100)/100).toString();

        if (this.prices && this.prices[roundedPrice]) {
            str+= this.prices[roundedPrice];
        }
        else {
            str+= this.priceTemplate.evaluate({price:price.toFixed(2)});
        }
        return str.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    clearSelect: function(element){
        for(var i=element.options.length-1;i>=0;i--){
            element.remove(i);
        }
    },

    getAttributeOptions: function(attributeId){
        if(this.config.attributes[attributeId]){
            return this.config.attributes[attributeId].options;
        }
    },

    reloadPrice: function(){
        if (this.config.disablePriceReload) {
            return;
        }

        this.reloadProduct();

        var price    = 0;
        var oldPrice = 0;
        var blank    = false;
        for(var i=this.settings.length-1;i>=0;i--){
            var selected = this.settings[i].options[this.settings[i].selectedIndex];
            if(selected.config){
                price    += parseFloat(selected.config.price);
                oldPrice += parseFloat(selected.config.oldPrice);
            } else {
                blank = true;
            }
        }

        if (this.config.product !== undefined) {
            price = this.config.productPrice;
            oldPrice = this.config.productOldPrice;
        }

        var oldPriceDisplay = $('old-price-'+this.config.productId),
            priceDisplay = $('product-price-'+this.config.productId);

        if (blank === true) {
            if(priceDisplay){
                var priceRange = optionsPrice.formatPrice(this.config.minPrice);

                if (this.config.minPrice !== this.config.maxPrice) {
                    priceRange += ' - ' + optionsPrice.formatPrice(this.config.maxPrice);
                }
                priceDisplay.select('.price')[0].textContent = priceRange;
            }
            if (oldPriceDisplay) {
                if (this.config.minOldPrice !== this.config.minPrice || this.config.maxOldPrice !== this.config.maxPrice) {
                    var oldPriceRange = optionsPrice.formatPrice(this.config.minOldPrice);

                    if (this.config.minOldPrice !== this.config.maxOldPrice) {
                        oldPriceRange += ' - ' + optionsPrice.formatPrice(this.config.maxOldPrice);
                    }

                    oldPriceDisplay.textContent = oldPriceRange;

                    oldPriceDisplay.show();
                } else {
                    oldPriceDisplay.hide();
                }
            }
        } else {
            optionsPrice.changePrice('config', {'price': price, 'oldPrice': oldPrice});
            optionsPrice.reload();

            if (oldPriceDisplay && priceDisplay) {
                if (price != oldPrice) {
                    priceDisplay.removeClassName('regular-price');
                    priceDisplay.addClassName('special-price');

                    var childDisplay = priceDisplay.select('.value');
                    if (childDisplay.length > 0) {
                        childDisplay[0].addClassName('special-price-value');
                    }

                    oldPriceDisplay.show();
                } else {
                    priceDisplay.removeClassName('special-price');
                    priceDisplay.addClassName('regular-price');

                    var childDisplay = priceDisplay.select('.value');
                    if (childDisplay.length > 0) {
                        childDisplay[0].removeClassName('special-price-value');
                    }

                    oldPriceDisplay.hide();
                }
            }

            return price;
        }
        //this.reloadOldPrice();
    },

    reloadOldPrice: function(){
        if ($('old-price-'+this.config.productId)) {

            var price = parseFloat(this.config.oldPrice);
            for(var i=this.settings.length-1;i>=0;i--){
                var selected = this.settings[i].options[this.settings[i].selectedIndex];
                if(selected.config){
                    var parsedOldPrice = parseFloat(selected.config.oldPrice);
                    price += isNaN(parsedOldPrice) ? 0 : parsedOldPrice;
                }
            }
            if (price < 0)
                price = 0;
            price = this.formatPrice(price);

            if($('old-price-'+this.config.productId)){
                $('old-price-'+this.config.productId).innerHTML = price;
            }

        }
    }
}