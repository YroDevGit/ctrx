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

    format(code, amount = null) {
        const symbol = Currency.currencies[code] || "";
        if (amount === null) {
            return symbol;
        }
        return symbol + Number(amount).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    usd(amount = null) {
        return this.format("USD", amount);
    }

    eur(amount = null) {
        return this.format("EUR", amount);
    }

    gbp(amount = null) {
        return this.format("GBP", amount);
    }

    yen(amount = null) {
        return this.format("JPY", amount);
    }

    peso(amount = null) {
        return this.format("PHP", amount);
    }

    cny(amount = null) {
        return this.format("CNY", amount);
    }

    krw(amount = null) {
        return this.format("KRW", amount);
    }

    inr(amount = null) {
        return this.format("INR", amount);
    }

    aud(amount = null) {
        return this.format("AUD", amount);
    }

    cad(amount = null) {
        return this.format("CAD", amount);
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

