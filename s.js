// ==================== ОРИГИНАЛЬНЫЙ КОД ИЗ index.html ====================
// Mobile Menu Toggle
const burger = document.querySelector('.burger');
const navLinks = document.querySelector('.nav-links');
const navItems = document.querySelectorAll('.nav-links li');
const hero = document.querySelector('.hero');
const logo = document.querySelector('.logo');

burger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    burger.classList.toggle('active');
    if (navLinks.classList.contains('active')) {
        hero.style.opacity = '0';
        hero.style.visibility = 'hidden';
        logo.style.opacity = '0';
        logo.style.visibility = 'hidden';
    } else {
        hero.style.opacity = '1';
        hero.style.visibility = 'visible';
        logo.style.opacity = '1';
        logo.style.visibility = 'visible';
    }
});

navItems.forEach(item => {
    if (item.querySelector('.dropdown')) {
        item.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                item.classList.toggle('active');
            }
        });
    }
});

// Color Picker
const colorOptions = document.querySelectorAll('.color-option');
const colorImageMap = {
    '1': { 'Черный': 'bmw1black.jpeg', 'Белый': 'bmw1white.jpeg', 'Синий': 'bmw1blue.jpeg', 'Розовый': 'bmw1pink.jpeg' },
    '2': { 'Черный': 'bmw3black.jpeg', 'Белый': 'bmw3white.jpeg', 'Синий': 'bmw3blue.jpg', 'Розовый': 'bmw3pink.jpeg' },
    '3': { 'Черный': 'bmw5black.jpeg', 'Белый': 'bmw5white.jpeg', 'Синий': 'bmw5blue.jpeg', 'Розовый': 'bmw5pink.jpg' }
};
colorOptions.forEach(option => {
    option.addEventListener('click', function() {
        const modelId = this.getAttribute('data-model');
        const colorName = this.getAttribute('data-color-name');
        const modelOptions = document.querySelectorAll(`.color-option[data-model="${modelId}"]`);
        modelOptions.forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        const carImage = document.getElementById(`model-${modelId}-img`);
        if (carImage && colorImageMap[modelId] && colorImageMap[modelId][colorName]) {
            carImage.src = colorImageMap[modelId][colorName];
        }
    });
});

// Slider
const slider = document.querySelector('.slider');
const slides = document.querySelectorAll('.slide');
const prevBtn = document.querySelector('.prev-btn');
const nextBtn = document.querySelector('.next-btn');
const dots = document.querySelectorAll('.dot');
let currentSlide = 0;
const totalSlides = slides.length;
function goToSlide(n) {
    if (n < 0) currentSlide = totalSlides - 1;
    else if (n >= totalSlides) currentSlide = 0;
    else currentSlide = n;
    slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    dots.forEach((dot, index) => dot.classList.toggle('active', index === currentSlide));
}
function nextSlide() { goToSlide(currentSlide + 1); }
function prevSlide() { goToSlide(currentSlide - 1); }
if (nextBtn) nextBtn.addEventListener('click', nextSlide);
if (prevBtn) prevBtn.addEventListener('click', prevSlide);
dots.forEach((dot, index) => dot.addEventListener('click', () => goToSlide(index)));

// Price Calculator
const calculatorForm = document.getElementById('price-calculator');
const totalPriceElement = document.getElementById('total-price');
if (calculatorForm && totalPriceElement) {
    function calculateTotalPrice() {
        let totalPrice = 0;
        totalPrice += parseInt(document.getElementById('model').value);
        totalPrice += parseInt(document.getElementById('color').value);
        totalPrice += parseInt(document.getElementById('interior').value);
        totalPrice += parseInt(document.getElementById('wheels').value);
        if (document.getElementById('panorama')?.checked) totalPrice += parseInt(document.getElementById('panorama').value);
        if (document.getElementById('premium-sound')?.checked) totalPrice += parseInt(document.getElementById('premium-sound').value);
        if (document.getElementById('assist-package')?.checked) totalPrice += parseInt(document.getElementById('assist-package').value);
        if (document.getElementById('sport-package')?.checked) totalPrice += parseInt(document.getElementById('sport-package').value);
        totalPriceElement.textContent = totalPrice.toLocaleString('ru-RU') + ' ₽';
    }
    calculatorForm.querySelectorAll('select, input').forEach(el => el.addEventListener('change', calculateTotalPrice));
    calculateTotalPrice();
}

// Modal
const modal = document.getElementById('contact-modal');
const contactBtn = document.getElementById('contact-btn');
const closeModal = document.querySelector('.close-modal');
if (contactBtn && modal) contactBtn.addEventListener('click', (e) => { e.preventDefault(); modal.style.display = 'flex'; });
if (closeModal && modal) closeModal.addEventListener('click', () => { modal.style.display = 'none'; });
if (modal) window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

// Form validation helpers
function validateName(name) { return name && name.trim().length >= 2; }
function validatePhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    return cleaned.length === 11 && cleaned[0] === '7';
}
function validateMessage(msg) { return msg && msg.trim().length >= 10; }
function showError(input, message) {
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) existingError.remove();
    input.classList.remove('error');
    if (message) {
        input.classList.add('error');
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.style.cssText = 'color:#e91e63;font-size:0.8rem;margin-top:0.3rem;';
        errorElement.textContent = message;
        input.parentNode.appendChild(errorElement);
    }
}
function handleFormSubmit(form, e) {
    e.preventDefault();
    const nameInput = form.querySelector('input[type="text"]');
    const phoneInput = form.querySelector('input[type="tel"]');
    const messageInput = form.querySelector('textarea');
    let isValid = true;
    if (!validateName(nameInput.value)) { showError(nameInput, 'Имя должно содержать минимум 2 символа'); isValid = false; } else showError(nameInput, null);
    if (!validatePhone(phoneInput.value)) { showError(phoneInput, 'Номер телефона должен состоять из 11 цифр и начинаться с 7'); isValid = false; } else showError(phoneInput, null);
    if (!validateMessage(messageInput.value)) { showError(messageInput, 'Поле "О себе" должно содержать минимум 10 символов'); isValid = false; } else showError(messageInput, null);
    if (!isValid) { const fm = form.querySelector('.form-message'); if(fm){fm.textContent='Пожалуйста, исправьте ошибки';fm.className='form-message error';fm.style.display='block';} return; }
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Отправка...';
    submitBtn.disabled = true;
    setTimeout(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        const fm = form.querySelector('.form-message');
        if(fm){fm.textContent='Спасибо! Ваша заявка отправлена.';fm.className='form-message success';fm.style.display='block';setTimeout(()=>fm.style.display='none',5000);}
        else alert('Спасибо! Заявка отправлена.');
        form.reset();
        form.querySelectorAll('.error-message').forEach(e=>e.remove());
        form.querySelectorAll('.error').forEach(e=>e.classList.remove('error'));
    }, 1500);
}
const contactForm = document.getElementById('contact-form');
const modalContactForm = document.getElementById('modal-contact-form');
if (contactForm) contactForm.addEventListener('submit', (e) => handleFormSubmit(contactForm, e));
if (modalContactForm) modalContactForm.addEventListener('submit', (e) => handleFormSubmit(modalContactForm, e));

// Smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({ top: targetElement.offsetTop - 80, behavior: 'smooth' });
            if (navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                burger.classList.remove('active');
                hero.style.opacity = '1'; hero.style.visibility = 'visible';
                logo.style.opacity = '1'; logo.style.visibility = 'visible';
            }
        }
    });
});

// Phone mask
function formatPhone(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 0 && value[0] === '8') value = '7' + value.slice(1);
    if (value.length > 0) {
        let formattedValue = '+7 (';
        if (value.length > 1) formattedValue += value.slice(1, 4);
        if (value.length >= 4) formattedValue += ') ' + value.slice(4, 7);
        if (value.length >= 7) formattedValue += '-' + value.slice(7, 9);
        if (value.length >= 9) formattedValue += '-' + value.slice(9, 11);
        input.value = formattedValue;
    }
}
document.querySelectorAll('input[type="tel"]').forEach(input => {
    input.placeholder = '+7 (999) 999-99-99';
    input.addEventListener('input', function() { formatPhone(this); });
    input.addEventListener('blur', function() {
        if (!validatePhone(this.value)) showError(this, 'Номер должен содержать 11 цифр и начинаться с 7');
        else showError(this, null);
    });
    input.addEventListener('focus', function() { showError(this, null); });
});
document.querySelectorAll('input[type="text"][name="name"]').forEach(input => {
    input.addEventListener('blur', function() {
        if (!validateName(this.value)) showError(this, 'Имя должно содержать минимум 2 символа');
        else showError(this, null);
    });
});
document.querySelectorAll('textarea[name="message"]').forEach(input => {
    input.addEventListener('blur', function() {
        if (!validateMessage(this.value)) showError(this, 'Минимум 10 символов');
        else showError(this, null);
    });
});

// Закрытие меню при клике на ссылку
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            navLinks.classList.remove('active');
            burger.classList.remove('active');
            hero.style.opacity = '1'; hero.style.visibility = 'visible';
            logo.style.opacity = '1'; logo.style.visibility = 'visible';
        }
    });
});
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        navLinks.classList.remove('active');
        burger.classList.remove('active');
        hero.style.opacity = '1'; hero.style.visibility = 'visible';
        logo.style.opacity = '1'; logo.style.visibility = 'visible';
    }
});

// ==================== НОВЫЙ КОД ДЛЯ АНКЕТЫ (REST API) ====================
(function() {
    const form = document.getElementById('anketa-form');
    if (!form) return;
    const messageDiv = document.getElementById('api-message');
    const credentialsDiv = document.getElementById('api-credentials');

    function showMessage(text, isError = false) {
        messageDiv.innerHTML = `<div class="alert ${isError ? 'error' : 'success'}">${text}</div>`;
        messageDiv.style.display = 'block';
        setTimeout(() => messageDiv.style.display = 'none', 5000);
    }
    function showCredentials(login, password) {
        credentialsDiv.innerHTML = `<div class="credentials-box"><strong>Ваши учётные данные:</strong><br>Логин: ${login}<br>Пароль: ${password}<br><small>Они сохранены в браузере. При следующем визите вы сможете редактировать данные.</small></div>`;
        credentialsDiv.style.display = 'block';
        setTimeout(() => credentialsDiv.style.display = 'none', 10000);
    }

    let authData = null;
    try {
        const stored = localStorage.getItem('anketa_auth');
        if (stored) authData = JSON.parse(stored);
    } catch(e) {}

    // Загрузка данных для авторизованного пользователя
    if (authData && authData.id && authData.login && authData.password) {
        fetch(`/api/application/${authData.id}`, {
            headers: { 'Authorization': 'Basic ' + btoa(authData.login + ':' + authData.password) }
        })
        .then(res => res.ok ? res.json() : null)
        .then(result => {
            if (result && result.success && result.data) {
                const d = result.data;
                form.querySelector('[name="full_name"]').value = d.full_name;
                form.querySelector('[name="phone"]').value = d.phone;
                form.querySelector('[name="email"]').value = d.email;
                form.querySelector('[name="birth_date"]').value = d.birth_date;
                const genderRadios = form.querySelectorAll('[name="gender"]');
                genderRadios.forEach(r => { if (r.value === d.gender) r.checked = true; });
                const select = form.querySelector('[name="languages[]"]');
                Array.from(select.options).forEach(opt => { opt.selected = d.languages.includes(opt.value); });
                form.querySelector('[name="bio"]').value = d.bio;
                form.querySelector('[name="contract_agreed"]').checked = d.contract_agreed;
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.textContent = 'Обновить данные';
            }
        }).catch(console.warn);
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const json = {};
        formData.forEach((val, key) => {
            if (key === 'languages[]') {
                if (!json.languages) json.languages = [];
                json.languages.push(val);
            } else {
                json[key] = val;
            }
        });
        if (!json.languages) json.languages = [];
        json.contract_agreed = !!json.contract_agreed;

        let url = '/api/application';
        let method = 'POST';
        let headers = { 'Content-Type': 'application/json' };

        if (authData && authData.id) {
            url = `/api/application/${authData.id}`;
            method = 'PUT';
            headers['Authorization'] = 'Basic ' + btoa(authData.login + ':' + authData.password);
        }

        try {
            const response = await fetch(url, { method, headers, body: JSON.stringify(json) });
            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                    let errorHtml = '<div class="alert error">Ошибки:<ul>';
                    for (let f in result.errors) errorHtml += `<li>${result.errors[f]}</li>`;
                    errorHtml += '</ul></div>';
                    messageDiv.innerHTML = errorHtml;
                    messageDiv.style.display = 'block';
                } else {
                    showMessage(result.error || 'Ошибка сервера', true);
                }
                return;
            }

            if (method === 'POST') {
                const { id, login, password } = result;
                localStorage.setItem('anketa_auth', JSON.stringify({ id, login, password }));
                showCredentials(login, password);
                showMessage('Анкета успешно сохранена!');
                form.reset();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.textContent = 'Сохранить';
                authData = { id, login, password };
            } else {
                showMessage('Данные успешно обновлены!');
            }
        } catch (err) {
            showMessage('Ошибка сети: ' + err.message, true);
        }
    });
})();