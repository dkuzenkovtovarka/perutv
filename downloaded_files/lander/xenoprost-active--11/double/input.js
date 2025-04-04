let body = document.querySelector('body');
let currentDate = new Date();
currentDate.setHours(currentDate.getHours() + 24);

localStorage.getItem('currentDate') ?? localStorage.setItem('currentDate', currentDate.getTime());
if (localStorage.getItem('currentDate') && localStorage.getItem('currentDate') < Date.now()) {
    localStorage.clear()
}

document.querySelectorAll('a').forEach((el) => {
    el.onclick = (e) => {
        e.preventDefault();
        document.querySelector('#order_form').scrollIntoView({behavior: 'smooth', block: 'start'})
    }
})

let order = localStorage.getItem('order') ?? 0;
let parametr = new URLSearchParams(location.search);
localStorage.setItem('pixel', parametr.get('pixel') ?? 1);
localStorage.setItem('typepixel', parametr.get('typepixel') ?? 'Lead');

let country_code = '+91'
let minlength = 10;
let maxLength = 10

document.querySelectorAll('form').forEach((el) => {
    let btn = el.querySelector('button') ?? el.querySelector('input[type=submit]');
    let phone = el.phone;
    let name = el.name;
    if (!btn || !phone || !name) return
    phone.setAttribute('maxlength', maxLength + country_code.length)

    btn.setAttribute('disabled', 'true');
    btn.style.opacity = '0.5';

    phone.oninput = function (e) {
        this.value = this.value.replace(/[^\d]/gi, '');
        if (!this.value.startsWith(country_code)) {
            this.value = country_code + this.value.slice(country_code.length - 1);
        }
        if (this.value.length >= country_code.length + minlength && this.value.length <=country_code.length + maxLength) {
            btn.style.opacity = '1'
            btn.removeAttribute('disabled')
        } else {
            btn.style.opacity = '0.5';
            btn.setAttribute('disabled', 'true')
        }


    }



    phone.onclick = function (e) {
        if (!this.value.startsWith(country_code)) {
            this.value = country_code + this.value
        }

    }

    el.onsubmit = async function (e) {
        e.preventDefault();
        if (phone.value == localStorage.getItem('phone')) {
            location.href = 'double/double.html'
            return;
        }
        if (order == 2) {
            location.href = 'double/stop.html'
            return
        }
        btn.style.opacity = '0.5';
        btn.setAttribute('disabled', true);
        let result = await fetch('api.php', {
            method: 'POST',
            body: new FormData(this)
        });
        result = await result.json();
        console.log(result)
        if (result.result == 'ok') {
            let paramRes = new URLSearchParams(result)
            order++;
            localStorage.setItem('order', order)
            localStorage.setItem('phone', phone.value);
            localStorage.setItem('name', name.value);
            location.href = `success/index.php?${paramRes.toString()}`


        } else {
            alert('Compruebe si el número de teléfono está introducido correctamente. Si el número se ingresó correctamente, actualice la página y vuelva a intentarlo.');
            btn.style.opacity = '1';
            btn.removeAttribute('disabled');
        }
    }
})


// if(parametr.has('utm_medium') && !localStorage.getItem('order')) {
//     domonetka()
// }else {
//     document.querySelector('#iframe') ? document.querySelector('#iframe').remove() : null
// }


function domonetka() {
    let compainID = '';
    let dm = '';
    let html = `<iframe id="iframe" src="" frameborder="0"></iframe>`;
    body.insertAdjacentHTML('afterbegin', html);
    let iframe = document.querySelector('#iframe');
    iframe.style.position = 'fixed';
    iframe.style.top = '0';
    iframe.style.left = '0';
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.zIndex = '100000000';
    iframe.style.display = 'none';
    iframe.style.backgroundColor = 'white';
    if(parametr.get('ad_id')) parametr.delete('ad_id');
    parametr.set('dm', dm);
    iframe.style.overflow = 'auto';
    iframe.setAttribute('src', `${location.protocol}//${location.host}/${compainID}?${parametr.toString()}`);
    // iframe.setAttribute('src', `${location.protocol}//${location.host}/${compainID}?utm_medium=${parametr.get('utm_medium')}&dm=${parametr.get('dm')}`);


    history.pushState('1', '', location.href);
    history.pushState('2', '', location.href);

    window.onpopstate = function (event) {
        if (event.state === "1") {
            iframe.style.display = 'block';
            body.style.overflow = 'hidden'

        }

    };
}

if(parametr.get('pixel')) setCookie('pixel', parametr.get('pixel'), 24)

function setCookie(name, value, hours) {
    let expires = "";
    if (hours) {
        const date = new Date();
        date.setTime(date.getTime() + (hours * 60 * 60 * 1000)); // перевод часов в миллисекунды
        expires = "; expires=" + date.toUTCString(); // форматируем дату в UTC
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/"; // записываем куки
}


