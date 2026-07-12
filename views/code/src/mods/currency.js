class CtrCurrency {
    static currencies = {
        USD: "$",
        EUR: "€",
        GBP: "£",
        JPY: "¥",
        PHP: "₱",
        CNY: "¥",
        KRW: "₩",
        INR: "₹",
        AUD: "A$",
        CAD: "C$",
    };

    static formats = {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        numberFormat: true,
    }

    setFormat(format) {
        formats = { ...formats, ...format };
    }

    format(code, amount = null, defaultValue = "") {
        const symbol = CtrCurrency.currencies[code] || "";
        if (amount === null) {
            if (defaultValue && defaultValue != "") {
                amount = defaultValue;
            } else {
                return symbol;
            }
        }
        if (!CtrCurrency.formats.numberFormat) {
            return symbol + Number(amount);
        }
        return symbol + Number(amount).toLocaleString(undefined, {
            minimumFractionDigits: CtrCurrency.formats.minimumFractionDigits,
            maximumFractionDigits: CtrCurrency.formats.maximumFractionDigits,
        });
    }

    usd(amount = null, defaultValue = "") {
        return this.format("USD", amount, defaultValue);
    }

    eur(amount = null, defaultValue = "") {
        return this.format("EUR", amount, defaultValue);
    }

    gbp(amount = null, defaultValue = "") {
        return this.format("GBP", amount, defaultValue);
    }

    yen(amount = null, defaultValue = "") {
        return this.format("JPY", amount, defaultValue);
    }

    peso(amount = null, defaultValue = "") {
        return this.format("PHP", amount, defaultValue);
    }

    cny(amount = null, defaultValue = "") {
        return this.format("CNY", amount, defaultValue);
    }

    krw(amount = null, defaultValue = "") {
        return this.format("KRW", amount, defaultValue);
    }

    inr(amount = null, defaultValue = "") {
        return this.format("INR", amount, defaultValue);
    }

    aud(amount = null, defaultValue = "") {
        return this.format("AUD", amount, defaultValue);
    }

    cad(amount = null, defaultValue = "") {
        return this.format("CAD", amount, defaultValue);
    }
}

const CURRENCY = new CtrCurrency();
const Currency = CURRENCY;

if (typeof window !== "undefined") {
    window.CURRENCY = CURRENCY;
    window.Currency = CURRENCY;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = CURRENCY;
    module.exports = Currency;
}

export default Currency;