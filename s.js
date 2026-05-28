// Mobile Menu Toggle
const burger = document.querySelector('.burger');
const navLinks = document.querySelector('.nav-links');
const navItems = document.querySelectorAll('.nav-links li');
const hero = document.querySelector('.hero');
const logo = document.querySelector('.logo');

burger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    burger.classList.toggle('active');
    
    // Скрываем/показываем hero и logo при открытии/закрытии меню
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

// Mobile Dropdown Toggle
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

// Color Picker - обновленный код
const colorOptions = document.querySelectorAll('.color-option');

// Маппинг цветов к названиям файлов изображений
const colorImageMap = {
    '1': { // BMW 1 Series
        'Черный': 'bmw1black.jpeg',
        'Белый': 'bmw1white.jpeg',
        'Синий': 'bmw1blue.jpeg',
        'Розовый': 'bmw1pink.jpeg'
    },
    '2': { // BMW 3 Series
        'Черный': 'bmw3black.jpeg',
        'Белый': 'bmw3white.jpeg',
        'Синий': 'bmw3blue.jpg',
        'Розовый': 'bmw3pink.jpeg'
    },
    '3': { // BMW 5 Series
        'Черный': 'bmw5black.jpeg',
        'Белый': 'bmw5white.jpeg',
        'Синий': 'bmw5blue.jpeg',
        'Розовый': 'bmw5pink.jpg'
    }
};

colorOptions.forEach(option => {
    option.addEventListener('click', function() {
        const modelId = this.getAttribute('data-model');
        const colorName = this.getAttribute('data-color-name');
        
        // Remove active class from all options in this model group
        const modelOptions = document.querySelectorAll(`.color-option[data-model="${modelId}"]`);
        modelOptions.forEach(opt => {
            opt.classList.remove('active');
        });
        
        // Add active class to clicked option
        this.classList.add('active');
        
        // Change car image based on color
        const carImage = document.getElementById(`model-${modelId}-img`);
        if (carImage && colorImageMap[modelId] && colorImageMap[modelId][colorName]) {
            carImage.src = colorImageMap[modelId][colorName];
            carImage.alt = `BMW ${modelId === '1' ? '1 Series' : modelId === '2' ? '3 Series' : '5 Series'} ${colorName}`;
        }
    });
});

// Slider functionality
const slider = document.querySelector('.slider');
const slides = document.querySelectorAll('.slide');
const prevBtn = document.querySelector('.prev-btn');
const nextBtn = document.querySelector('.next-btn');
const dots = document.querySelectorAll('.dot');

let currentSlide = 0;
const totalSlides = slides.length;

function goToSlide(n) {
    if (n < 0) {
        currentSlide = totalSlides - 1;
    } else if (n >= totalSlides) {
        currentSlide = 0;
    } else {
        currentSlide = n;
    }
    
    slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    
    // Update dots
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
    });
}

function nextSlide() {
    goToSlide(currentSlide + 1);
}

function prevSlide() {
    goToSlide(currentSlide - 1);
}

// Event listeners for slider controls
if (nextBtn) nextBtn.addEventListener('click', nextSlide);
if (prevBtn) prevBtn.addEventListener('click', prevSlide);

// Event listeners for dots
dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        goToSlide(index);
    });
});

// Price Calculator
const calculatorForm = document.getElementById('price-calculator');
const totalPriceElement = document.getElementById('total-price');

if (calculatorForm && totalPriceElement) {
    function calculateTotalPrice() {
        let totalPrice = 0;
        
        // Base model price
        const modelSelect = document.getElementById('model');
        totalPrice += parseInt(modelSelect.value);
        
        // Color price
        const colorSelect = document.getElementById('color');
        totalPrice += parseInt(colorSelect.value);
        
        // Interior price
        const interiorSelect = document.getElementById('interior');
        totalPrice += parseInt(interiorSelect.value);
        
        // Wheels price
        const wheelsSelect = document.getElementById('wheels');
        totalPrice += parseInt(wheelsSelect.value);
        
        // Additional options
        const panorama = document.getElementById('panorama');
        if (panorama && panorama.checked) totalPrice += parseInt(panorama.value);
        
        const premiumSound = document.getElementById('premium-sound');
        if (premiumSound && premiumSound.checked) totalPrice += parseInt(premiumSound.value);
        
        const assistPackage = document.getElementById('assist-package');
        if (assistPackage && assistPackage.checked) totalPrice += parseInt(assistPackage.value);
        
        const sportPackage = document.getElementById('sport-package');
        if (sportPackage && sportPackage.checked) totalPrice += parseInt(sportPackage.value);
        
        // Format and display total price
        totalPriceElement.textContent = totalPrice.toLocaleString('ru-RU') + ' ₽';
    }

    // Add event listeners to all form elements
    const formElements = calculatorForm.querySelectorAll('select, input');
    formElements.forEach(element => {
        element.addEventListener('change', calculateTotalPrice);
    });

    // Initialize calculator
    calculateTotalPrice();
}

// Modal
const modal = document.getElementById('contact-modal');
const contactBtn = document.getElementById('contact-btn');
const closeModal = document.querySelector('.close-modal');

if (contactBtn && modal) {
    contactBtn.addEventListener('click', (e) => {
        e.preventDefault();
        modal.style.display = 'flex';
    });
}

if (closeModal && modal) {
    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });
}

if (modal) {
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Form Submission
const contactForm = document.getElementById('contact-form');
const modalContactForm = document.getElementById('modal-contact-form');
const formMessage = document.getElementById('form-message');

// Функции валидации
function validateName(name) {
    // Проверяем что поле не пустое и содержит хотя бы 2 символа
    return name && name.trim().length >= 2;
}

function validatePhone(phone) {
    // Удаляем все нецифровые символы
    const cleaned = phone.replace(/\D/g, '');
    // Проверяем что номер состоит из 11 цифр и начинается с 7
    return cleaned.length === 11 && cleaned[0] === '7';
}

function validateMessage(message) {
    // Проверяем что поле не пустое и содержит хотя бы 10 символов
    return message && message.trim().length >= 10;
}

// Функция показа ошибок
function showError(input, message) {
    // Удаляем старые ошибки
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Убираем старый класс ошибки
    input.classList.remove('error');
    
    // Если есть сообщение об ошибке, показываем его
    if (message) {
        input.classList.add('error');
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.style.color = '#e91e63';
        errorElement.style.fontSize = '0.8rem';
        errorElement.style.marginTop = '0.3rem';
        errorElement.textContent = message;
        input.parentNode.appendChild(errorElement);
    }
}

function handleFormSubmit(form, e) {
    e.preventDefault();
    
    // Находим поля
    const nameInput = form.querySelector('input[type="text"]');
    const phoneInput = form.querySelector('input[type="tel"]');
    const messageInput = form.querySelector('textarea');
    
    let isValid = true;
    
    // Валидация имени
    if (!validateName(nameInput.value)) {
        showError(nameInput, 'Имя должно содержать минимум 2 символа');
        isValid = false;
    } else {
        showError(nameInput, null);
    }
    
    // Валидация телефона
    if (!validatePhone(phoneInput.value)) {
        showError(phoneInput, 'Номер телефона должен состоять из 11 цифр и начинаться с 7');
        isValid = false;
    } else {
        showError(phoneInput, null);
    }
    
    // Валидация сообщения
    if (!validateMessage(messageInput.value)) {
        showError(messageInput, 'Поле "О себе" должно содержать минимум 10 символов');
        isValid = false;
    } else {
        showError(messageInput, null);
    }
    
    if (!isValid) {
        // Показываем общее сообщение об ошибке
        const formMessage = form.querySelector('.form-message') || document.getElementById('form-message');
        if (formMessage) {
            formMessage.textContent = 'Пожалуйста, исправьте ошибки в форме';
            formMessage.className = 'form-message error';
            formMessage.style.display = 'block';
        }
        return;
    }
    
    // Показать состояние загрузки
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Отправка...';
    submitBtn.disabled = true;
    
    // Симуляция API вызова
    setTimeout(() => {
        // Симуляция успешной отправки
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        
        // Показать сообщение об успехе
        const formMessage = form.querySelector('.form-message') || document.getElementById('form-message');
        if (formMessage) {
            formMessage.textContent = 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.';
            formMessage.className = 'form-message success';
            formMessage.style.display = 'block';
            
            // Скрыть сообщение через 5 секунд
            setTimeout(() => {
                formMessage.style.display = 'none';
            }, 5000);
        } else {
            alert('Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.');
            if (modal) modal.style.display = 'none';
        }
        
        // Сбросить форму
        form.reset();
        
        // Убрать сообщения об ошибках
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(error => error.remove());
        
        const errorInputs = form.querySelectorAll('.error');
        errorInputs.forEach(input => input.classList.remove('error'));
    }, 1500);
}

if (contactForm) {
    contactForm.addEventListener('submit', (e) => handleFormSubmit(contactForm, e));
}

if (modalContactForm) {
    modalContactForm.addEventListener('submit', (e) => handleFormSubmit(modalContactForm, e));
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
            
            // Close mobile menu if open
            if (navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                burger.classList.remove('active');
                hero.style.opacity = '1';
                hero.style.visibility = 'visible';
                logo.style.opacity = '1';
                logo.style.visibility = 'visible';
            }
        }
    });
});

// Добавляем маску для телефона
function formatPhone(input) {
    // Удаляем все нецифровые символы
    let value = input.value.replace(/\D/g, '');
    
    // Если номер начинается с 8, меняем на 7
    if (value.length > 0 && value[0] === '8') {
        value = '7' + value.slice(1);
    }
    
    // Форматируем номер
    if (value.length > 0) {
        let formattedValue = '+7 (';
        
        if (value.length > 1) {
            formattedValue += value.slice(1, 4);
        }
        if (value.length >= 4) {
            formattedValue += ') ' + value.slice(4, 7);
        }
        if (value.length >= 7) {
            formattedValue += '-' + value.slice(7, 9);
        }
        if (value.length >= 9) {
            formattedValue += '-' + value.slice(9, 11);
        }
        
        input.value = formattedValue;
    }
}

// Добавляем обработчики событий для всех полей формы
document.addEventListener('DOMContentLoaded', function() {
    // Обработчики для полей имени
    const nameInputs = document.querySelectorAll('input[type="text"][name="name"]');
    
    nameInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!validateName(this.value)) {
                showError(this, 'Имя должно содержать минимум 2 символа');
            } else {
                showError(this, null);
            }
        });
        
        input.addEventListener('focus', function() {
            showError(this, null);
        });
    });
    
    // Обработчики для полей телефона
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    
    phoneInputs.forEach(input => {
        // Добавляем плейсхолдер
        input.placeholder = '+7 (999) 999-99-99';
        
        // Обработчик ввода
        input.addEventListener('input', function() {
            formatPhone(this);
        });
        
        // Обработчик потери фокуса для валидации
        input.addEventListener('blur', function() {
            if (!validatePhone(this.value)) {
                showError(this, 'Номер телефона должен состоять из 11 цифр и начинаться с 7');
            } else {
                showError(this, null);
            }
        });
        
        // Обработчик получения фокуса - убираем ошибку
        input.addEventListener('focus', function() {
            showError(this, null);
        });
    });
    
    // Обработчики для полей сообщения
    const messageInputs = document.querySelectorAll('textarea[name="message"]');
    
    messageInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!validateMessage(this.value)) {
                showError(this, 'Поле "О себе" должно содержать минимум 10 символов');
            } else {
                showError(this, null);
            }
        });
        
        input.addEventListener('focus', function() {
            showError(this, null);
        });
    });
});

// Закрытие меню при клике на ссылку в мобильной версии
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            navLinks.classList.remove('active');
            burger.classList.remove('active');
            hero.style.opacity = '1';
            hero.style.visibility = 'visible';
            logo.style.opacity = '1';
            logo.style.visibility = 'visible';
        }
    });
});

// Закрытие меню при изменении размера окна на десктопный
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        navLinks.classList.remove('active');
        burger.classList.remove('active');
        hero.style.opacity = '1';
        hero.style.visibility = 'visible';
        logo.style.opacity = '1';
        logo.style.visibility = 'visible';
    }
});
