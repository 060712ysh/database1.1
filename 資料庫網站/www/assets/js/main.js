// 側邊欄選單點擊互動
document.addEventListener('DOMContentLoaded', function() {
    // 為側邊欄連結添加 active 類
    const currentUrl = window.location.href;
    const links = document.querySelectorAll('.sidebar a');
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (currentUrl.includes(href)) {
            link.style.borderLeft = '4px solid #3498db';
            link.style.paddingLeft = '16px';
            link.style.backgroundColor = '#34495e';
        }
    });
});

// 通用表單驗證
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            input.style.borderColor = '#ccc';
        }
    });
    
    return isValid;
}

// 提示確認
function confirmAction(message) {
    return confirm(message || '確定要執行此操作嗎？');
}

// 顯示成功提示
function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'card';
    alert.style.cssText = 'background:#d4edda; border-left:4px solid #28a745; margin:10px 0;';
    alert.innerHTML = '<strong>✓ 成功：</strong>' + message;
    document.body.insertBefore(alert, document.body.firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// 顯示錯誤提示
function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'card';
    alert.style.cssText = 'background:#f8d7da; border-left:4px solid #dc3545; margin:10px 0;';
    alert.innerHTML = '<strong>✗ 錯誤：</strong>' + message;
    document.body.insertBefore(alert, document.body.firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// 數字輸入驗證 (成績輸入)
function validateScore(input) {
    const value = parseFloat(input.value);
    if (isNaN(value) || value < 0 || value > 100) {
        input.value = '';
        alert('請輸入 0-100 之間的成績');
    }
}
