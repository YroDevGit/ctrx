class CtrDate {
    get_name($date, $format = "F d, Y H:i:s") {
        const d = new Date($date);

        let hours24 = d.getHours();
        let hours12 = hours24 % 12 || 12;
        let ampm = hours24 < 12 ? "AM" : "PM";

        const map = {
            F: d.toLocaleString("en-US", { month: "long" }), // Full month
            d: String(d.getDate()).padStart(2, "0"),         // Day with leading zero
            Y: d.getFullYear(),                              // Full year
            H: String(hours24).padStart(2, "0"),             // 24h hour
            h: String(hours12).padStart(2, "0"),             // 12h hour
            i: String(d.getMinutes()).padStart(2, "0"),      // Minutes
            s: String(d.getSeconds()).padStart(2, "0"),      // Seconds
            A: ampm,                                         // AM/PM
            a: ampm.toLowerCase(),                           // am/pm
            m: String(parseInt(d.getMonth())+1).padStart(2, "0"),
            M: d.getUTCMonth()
        };

        return $format.replace(/F|d|Y|H|h|i|s|A|m|M|a/g, m => map[m]);
    }

    format(date, format = "Y-m-d H:i:s") {
        return this.get_name(date, format);
    }

    change_date(date, interval) {
        const d = new Date(date);

        if (interval) {
            // mag gamit:  like "+20 minutes", "-2 days", "+1 hour"
            const match = interval.match(/^([+-]?\d+)\s*(year|month|day|hour|minute|second)s?$/i);
            if (match) {
                const value = parseInt(match[1], 10);
                const unit = match[2].toLowerCase();

                switch (unit) {
                    case "year":
                        d.setFullYear(d.getFullYear() + value);
                        break;
                    case "month":
                        d.setMonth(d.getMonth() + value);
                        break;
                    case "day":
                        d.setDate(d.getDate() + value);
                        break;
                    case "hour":
                        d.setHours(d.getHours() + value);
                        break;
                    case "minute":
                        d.setMinutes(d.getMinutes() + value);
                        break;
                    case "second":
                        d.setSeconds(d.getSeconds() + value);
                        break;
                }
            }
        }

        const pad = n => String(n).padStart(2, "0");
        return (
            d.getFullYear() +
            "-" + pad(d.getMonth() + 1) +
            "-" + pad(d.getDate()) +
            " " + pad(d.getHours()) +
            ":" + pad(d.getMinutes()) +
            ":" + pad(d.getSeconds())
        );
    }


    get_date(date, format = "Y-m-d H:i:s") {
        const d = new Date(date);

        let hours24 = d.getHours();
        let hours12 = hours24 % 12 || 12;
        let ampm = hours24 < 12 ? "AM" : "PM";

        const map = {
            Y: d.getFullYear(),
            m: String(d.getMonth() + 1).padStart(2, "0"),
            d: String(d.getDate()).padStart(2, "0"),
            H: String(hours24).padStart(2, "0"),
            h: String(hours12).padStart(2, "0"),
            i: String(d.getMinutes()).padStart(2, "0"),
            s: String(d.getSeconds()).padStart(2, "0"),
            A: ampm,
            a: ampm.toLowerCase()
        };

        return format.replace(/Y|m|d|H|h|i|s|A|a/g, m => map[m]);
    }

    now(format = "Y-m-d H:i:s") {
        const d = new Date();

        let hours24 = d.getHours();
        let hours12 = hours24 % 12 || 12;
        let ampm = hours24 < 12 ? "AM" : "PM";

        const map = {
            Y: d.getFullYear(),
            m: String(d.getMonth() + 1).padStart(2, "0"),
            d: String(d.getDate()).padStart(2, "0"),
            H: String(hours24).padStart(2, "0"),
            h: String(hours12).padStart(2, "0"),
            i: String(d.getMinutes()).padStart(2, "0"),
            s: String(d.getSeconds()).padStart(2, "0"),
            A: ampm,
            a: ampm.toLowerCase()
        };

        return format.replace(/Y|m|d|H|h|i|s|A|a/g, m => map[m]);
    }

    dateInterval(date1, date2, type = "days") {
        const start = new Date(date1);
        const end = new Date(date2);
        const diffMs = end - start;
        const diffSeconds = diffMs / 1000;
        const diffMinutes = diffSeconds / 60;
        const diffHours = diffMinutes / 60;
        const diffDays = diffHours / 24;

        switch (type.toLowerCase()) {
            case "second":
            case "seconds":
                return diffSeconds;

            case "minute":
            case "minutes":
                return diffMinutes;

            case "hour":
            case "hours":
                return diffHours;

            case "day":
            case "days":
                return diffDays;

            case "week":
            case "weeks":
                return diffDays / 7;

            case "month":
            case "months": {
                const years = end.getFullYear() - start.getFullYear();
                const months = years * 12 + (end.getMonth() - start.getMonth());
                const dayAdjust = end.getDate() - start.getDate();
                return months + (dayAdjust / 30);
            }

            case "year":
            case "years": {
                const years = end.getFullYear() - start.getFullYear();
                const monthDiff = end.getMonth() - start.getMonth();
                const dayDiff = end.getDate() - start.getDate();
                return years + (monthDiff / 12) + (dayDiff / 365);
            }

            default:
                throw new Error("Invalid interval type: " + type);
        }
    }

    intervalName(date1, date2 = new Date()) {
        const start = new Date(date1);
        const end = new Date(date2);

        const diffMs = end - start;
        const isFuture = diffMs < 0;
        const diffSeconds = Math.abs(diffMs) / 1000;

        let value, unit;

        if (diffSeconds < 60) {
            value = Math.floor(diffSeconds);
            unit = "second";
        } else if (diffSeconds < 3600) {
            value = Math.floor(diffSeconds / 60);
            unit = "minute";
        } else if (diffSeconds < 86400) {
            value = Math.floor(diffSeconds / 3600);
            unit = "hour";
        } else if (diffSeconds < 2592000) {
            value = Math.floor(diffSeconds / 86400);
            unit = "day";
        } else if (diffSeconds < 31536000) {
            value = Math.floor(diffSeconds / 2592000);
            unit = "month";
        } else {
            value = Math.floor(diffSeconds / 31536000);
            unit = "year";
        }

        const plural = value !== 1 ? "s" : "";
        const suffix = isFuture ? "left" : "ago";

        return `${value} ${unit}${plural} ${suffix}`;
    }

    datePicker(options) {
        if (typeof options !== "string") {
            options.fields = options.fields ?? options.field;
        }
        if (typeof options === 'string') {
            options = { fields: [options] };
        } else if (options && !Array.isArray(options.fields) && typeof options.fields === 'string') {
            options.fields = [options.fields];
        } else if (options && options.fields instanceof Set) {
            options.fields = Array.from(options.fields);
        } else if (options && typeof options.fields === 'object') {
            options.fields = Object.values(options.fields);
        }

        const fields = options.fields || [];


        fields.forEach(selector => {
            const input = document.querySelector(selector);
            input.setAttribute("autocomplete", "off");
            input.setAttribute("readonly", true);
            let mx = input.getAttribute("maxdate") ?? null;
            let mn = input.getAttribute("mindate") ?? null;
            let etime = input.getAttribute("time") ?? null;
            const maxDate = mx ? new Date((mx === 'today' || mx === 'now') ? new Date() : mx) : null;
            const minDate = mn ? new Date((mn === 'today' || mn === 'now') ? new Date() : mn) : null;
            const enableTime = etime || false;

            if (!input) return;

            const overlay = document.createElement('div');
            Object.assign(overlay.style, {
                position: 'fixed',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                background: 'rgba(0,0,0,0.5)',
                display: 'none',
                zIndex: '999998',
            });
            document.body.appendChild(overlay);
            const container = document.createElement('div');
            container.className = 'ctr-calendar';
            const title = document.createElement("div");
            title.innerHTML = input.getAttribute("ctrtitle") ?? "CodeTazer Calendar";
            title.style.color = "black";
            title.style.fontFamily = "monospaced";
            title.setAttribute("align", "center");
            title.style.padding = "5px 0px";
            container.appendChild(title);
            Object.assign(container.style, {
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                border: '1px solid #ccc',
                background: options.bg ?? "rgb(144 228 219)",
                padding: '10px',
                display: 'none',
                zIndex: '999999',
                width: '280px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                fontFamily: 'Arial, sans-serif',
                borderRadius: '8px'
            });
            document.body.appendChild(container);

            const header = document.createElement('div');
            header.style.display = 'flex';
            header.style.justifyContent = 'space-between';
            header.style.alignItems = 'center';
            header.style.marginBottom = '8px';
            container.appendChild(header);

            const prevBtn = document.createElement('button');
            prevBtn.innerHTML = '&#10094;';
            Object.assign(prevBtn.style, { cursor: 'pointer', background: '#f0f0f0', border: 'none', padding: '5px 10px', borderRadius: '5px', transition: '0.2s' });
            prevBtn.addEventListener('mouseenter', () => prevBtn.style.background = '#e0e0e0');
            prevBtn.addEventListener('mouseleave', () => prevBtn.style.background = '#f0f0f0');

            const nextBtn = document.createElement('button');
            nextBtn.innerHTML = '&#10095;';
            Object.assign(nextBtn.style, { cursor: 'pointer', background: '#f0f0f0', border: 'none', padding: '5px 10px', borderRadius: '5px', transition: '0.2s' });
            nextBtn.addEventListener('mouseenter', () => nextBtn.style.background = '#e0e0e0');
            nextBtn.addEventListener('mouseleave', () => nextBtn.style.background = '#f0f0f0');

            const monthSelect = document.createElement('select');
            monthSelect.setAttribute("id", "CODETZRD-SELMONTH");
            Object.assign(monthSelect.style, { padding: '4px', borderRadius: '5px', border: '1px solid #ccc', cursor: 'pointer', background: '#fafafa' });
            for (let i = 0; i < 12; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.text = new Date(0, i).toLocaleString('default', { month: 'long' });
                monthSelect.add(opt);
            }

            const yearSelect = document.createElement('select');
            yearSelect.setAttribute("id", "CODETZRD-SELYEAR");

            Object.assign(yearSelect.style, { padding: '4px', borderRadius: '5px', border: '1px solid #ccc', cursor: 'pointer', background: '#fafafa' });
            const currentYear = new Date().getFullYear();
            for (let i = currentYear - 50; i <= currentYear + 50; i++) {
                const opt = document.createElement('option'); opt.value = i; opt.text = i; yearSelect.add(opt);
            }

            const centerContainer = document.createElement('div');
            centerContainer.style.display = 'flex';
            centerContainer.style.gap = '5px';
            centerContainer.appendChild(monthSelect);
            centerContainer.appendChild(yearSelect);

            header.appendChild(prevBtn);
            header.appendChild(centerContainer);
            header.appendChild(nextBtn);

            const daysGrid = document.createElement('div');
            daysGrid.style.display = 'grid';
            daysGrid.style.gridTemplateColumns = 'repeat(7,1fr)';
            daysGrid.style.gap = '3px';
            daysGrid.style.marginBottom = '8px';
            container.appendChild(daysGrid);

            let hourSelect, minSelect, ampmSelect;
            if (enableTime) {
                const timeContainer = document.createElement('div');
                timeContainer.style.display = 'flex';
                timeContainer.style.marginBottom = '8px';
                timeContainer.style.padding = "8px 0px";
                container.appendChild(timeContainer);
                hourSelect = document.createElement('select');
                for (let h = 1; h <= 12; h++) { const opt = document.createElement('option'); opt.value = h; opt.text = h.toString().padStart(2, '0'); hourSelect.add(opt); }
                minSelect = document.createElement('select');
                for (let m = 0; m < 60; m++) { const opt = document.createElement('option'); opt.value = m; opt.text = m.toString().padStart(2, '0'); minSelect.add(opt); }
                ampmSelect = document.createElement('select');
                ['AM', 'PM'].forEach(a => { const o = document.createElement('option'); o.value = a; o.text = a; ampmSelect.add(o); });
                timeContainer.appendChild(hourSelect);
                timeContainer.appendChild(document.createTextNode(':'));
                timeContainer.appendChild(minSelect);
                timeContainer.appendChild(ampmSelect);
                [hourSelect, minSelect, ampmSelect].forEach(b => Object.assign(b.style, { cursor: "pointer", borderRadius: "5px", border: "1px solid rgb(204, 204, 204)", marginRight: "5px" }));
            }

            const buttonContainer = document.createElement('div');
            buttonContainer.style.display = 'flex';
            buttonContainer.style.justifyContent = 'space-between';
            container.appendChild(buttonContainer);

            const okBtn = document.createElement('button'); okBtn.textContent = '👍';
            const TodayBtn = document.createElement('button'); TodayBtn.textContent = 'now';
            const resetBtn = document.createElement('button'); resetBtn.textContent = 'clear';
            const cancelBtn = document.createElement('button'); cancelBtn.textContent = '❌';
            [okBtn, resetBtn, cancelBtn, TodayBtn].forEach(b => Object.assign(b.style, { cursor: 'pointer', padding: '5px 10px', borderRadius: '5px', border: '1px solid #ccc', background: '#f9f9f9', transition: '0.2s' }));
            [okBtn, resetBtn, cancelBtn, TodayBtn].forEach(b => { b.addEventListener('mouseenter', () => b.style.background = '#e0e0e0'); b.addEventListener('mouseleave', () => b.style.background = '#f9f9f9'); });

            buttonContainer.appendChild(cancelBtn);
            buttonContainer.appendChild(resetBtn);
            buttonContainer.appendChild(TodayBtn);
            buttonContainer.appendChild(okBtn);

            let selectedDay = null;
            let currentDate = new Date();

            function renderCalendar(date) {
                const year = date.getFullYear();
                const month = date.getMonth();
                monthSelect.value = month;
                yearSelect.value = year;

                daysGrid.innerHTML = '';
                const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                weekDays.forEach(d => {
                    const cell = document.createElement('div');
                    cell.textContent = d;
                    cell.style.fontWeight = 'bold';
                    cell.style.textAlign = 'center';
                    cell.style.color = d === 'Sun' ? 'red' : '#000';
                    daysGrid.appendChild(cell);
                });

                const firstDay = new Date(year, month, 1).getDay();
                const lastDate = new Date(year, month + 1, 0).getDate();
                const nowdatetoday = new Date().getDate();
                const nowdatetomonth = new Date().getMonth();
                const nowdatetoyear = new Date().getFullYear();
                for (let i = 0; i < firstDay; i++) daysGrid.innerHTML += '<div></div>';

                for (let d = 1; d <= lastDate; d++) {
                    const dayDiv = document.createElement('div');
                    dayDiv.textContent = d;
                    dayDiv.style.textAlign = 'center';
                    dayDiv.style.cursor = 'pointer';
                    dayDiv.style.borderRadius = '4px';
                    const dayOfWeek = (firstDay + d - 1) % 7;
                    const thisDate = new Date(year, month, d);

                    dayDiv.style.color = (dayOfWeek === 0) ? 'red' : '#000';

                    const dayOnly = new Date(thisDate.getFullYear(), thisDate.getMonth(), thisDate.getDate());
                    const minDay = minDate ? new Date(minDate.getFullYear(), minDate.getMonth(), minDate.getDate()) : null;
                    const maxDay = maxDate ? new Date(maxDate.getFullYear(), maxDate.getMonth(), maxDate.getDate()) : null;

                    if (enableTime) {
                        if ((minDay && dayOnly < minDay) || (maxDay && dayOnly > maxDay)) {
                            dayDiv.style.color = '#ccc';
                            dayDiv.style.cursor = 'not-allowed';
                        } else {
                            dayDiv.addEventListener('click', (e) => {
                                selectedDay = d;
                                renderCalendar(currentDate);
                                e.stopPropagation();
                            });
                        }
                    } else {
                        if ((minDay && dayOnly <= minDay) || (maxDay && dayOnly >= maxDay)) {
                            dayDiv.style.color = '#ccc';
                            dayDiv.style.cursor = 'not-allowed';
                        } else {
                            dayDiv.addEventListener('click', (e) => {
                                selectedDay = d;
                                renderCalendar(currentDate);
                                e.stopPropagation();
                            });
                        }
                    }

                    dayDiv.setAttribute("id", "CODETZRD-" + yearSelect.value + "-" + (parseInt(monthSelect.value) + 1) + "-" + d);
                    if (selectedDay === d) {
                        dayDiv.style.background = '#007bff';
                        dayDiv.style.color = '#fff';
                    }

                    if (nowdatetoday === d && nowdatetomonth === month && nowdatetoyear == year) {
                        dayDiv.style.border = "solid 1px #007bff";
                    }

                    dayDiv.addEventListener('mouseenter', () => { if (!selectedDay || selectedDay !== d) dayDiv.style.background = '#eee'; });
                    dayDiv.addEventListener('mouseleave', () => { if (!selectedDay || selectedDay !== d) dayDiv.style.background = ''; });

                    daysGrid.appendChild(dayDiv);
                }
            }

            prevBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(currentDate); });
            nextBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(currentDate); });
            monthSelect.addEventListener('change', () => { currentDate.setMonth(parseInt(monthSelect.value)); renderCalendar(currentDate); });
            yearSelect.addEventListener('change', () => { currentDate.setFullYear(parseInt(yearSelect.value)); renderCalendar(currentDate); });

            okBtn.addEventListener('click', () => {
                if (!selectedDay) return;

                const y = currentDate.getFullYear(), m = currentDate.getMonth() + 1, d = selectedDay;
                let val = `${y}-${m.toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;

                let selectedDate;
                if (enableTime) {
                    const h = parseInt(hourSelect.value);
                    const mi = parseInt(minSelect.value);
                    const ap = ampmSelect.value;
                    const hour24 = ap === 'PM' && h !== 12 ? h + 12 : (ap === 'AM' && h === 12 ? 0 : h);
                    selectedDate = new Date(`${y}-${m}-${d} ${hour24}:${mi}`);
                    if (minDate && selectedDate < minDate) {
                        alert(`⛔ Date/Time should be ahead of the minimum date: ${mn}`);
                        return;
                    }
                    if (maxDate && selectedDate > maxDate) {
                        alert(`⛔ Date/Time should be behind the maximum date: ${mx}`);
                        return;
                    }
                    val += ` ${h.toString().padStart(2, '0')}:${mi.toString().padStart(2, '0')} ${ap}`;
                } else {
                    selectedDate = new Date(`${y}-${m}-${d}`);
                }

                input.value = val;
                container.style.display = 'none';
                overlay.style.display = 'none';
            });

            TodayBtn.addEventListener('click', () => {
                const thedate = new Date();
                const y = thedate.getFullYear(), m = thedate.getMonth() + 1, d = thedate.getDate();
                let val = `${y}-${m.toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
                let selectedDate;
                if (enableTime) {
                    const h = thedate.getHours() % 12 || 12;
                    const mi = thedate.getMinutes();
                    const ap = thedate.getHours() >= 12 ? 'PM' : 'AM';
                    const hour24 = ap === 'PM' && h !== 12 ? h + 12 : (ap === 'AM' && h === 12 ? 0 : h);
                    selectedDate = new Date(`${y}-${m}-${d} ${hour24}:${mi}`);
                    if (minDate && selectedDate < minDate) {
                        alert(`⛔ Date/Time should be ahead of the minimum date: ${mn}`);
                        return;
                    }
                    if (maxDate && selectedDate > maxDate) {
                        alert(`⛔ Date/Time should be behind the maximum date: ${mx}`);
                        return;
                    }
                    val += ` ${h}:${mi} ${ap}`;
                }
                input.value = val;
                container.style.display = 'none';
                overlay.style.display = 'none';
            });

            resetBtn.addEventListener('click', () => { input.value = ''; selectedDay = null; renderCalendar(currentDate); cancelBtn.click(); });
            cancelBtn.addEventListener('click', () => { container.style.display = 'none'; overlay.style.display = 'none'; });

            input.addEventListener('click', (e) => {
                container.style.display = 'block';
                overlay.style.display = 'block';
                if (!input.value) {
                    currentDate = new Date();
                    renderCalendar(currentDate);
                    if (enableTime) {
                        let ap = currentDate.getHours() >= 12 ? 'PM' : 'AM';
                        let GHour = currentDate.getHours() % 12 || 12;
                        let GMinute = parseInt(currentDate.getMinutes());
                        hourSelect.value = GHour;
                        minSelect.value = GMinute;
                        ampmSelect.value = ap;
                    }
                    e.stopPropagation();
                } else {
                    currentDate = new Date(input.value);
                    let ff = currentDate.getFullYear() + "-" + (parseInt(currentDate.getMonth() + 1)) + "-" + currentDate.getDate();
                    document.querySelector("#CODETZRD-SELMONTH").value = parseInt(currentDate.getMonth()) + 1;
                    document.querySelector("#CODETZRD-SELYEAR").value = currentDate.getFullYear();
                    renderCalendar(currentDate);
                    if (enableTime) {
                        let ap = currentDate.getHours() >= 12 ? 'PM' : 'AM';
                        let GHour = currentDate.getHours() % 12 || 12;
                        let GMinute = parseInt(currentDate.getMinutes());
                        hourSelect.value = GHour;
                        minSelect.value = GMinute;
                        ampmSelect.value = ap;
                    }
                    document.querySelector("#CODETZRD-" + ff).click();
                    e.stopPropagation();
                }
            });

            overlay.addEventListener('click', () => {
                container.style.display = 'none';
                overlay.style.display = 'none';
            });

            document.addEventListener('click', e => {
                if (!container.contains(e.target) && e.target !== input) {
                    container.style.display = 'none';
                    overlay.style.display = 'none';
                }
            });
        });
    }
}

const CtrDATE = new CtrDate();

if (typeof window !== "undefined") {
    window.CtrDATE = CtrDATE;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = CtrDATE;
}

export default CtrDATE;