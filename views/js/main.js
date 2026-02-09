function copyToClipboard() {
    const command = 'composer create-project yourname/ctrx ctrapp';
    navigator.clipboard.writeText(command).then(() => {
      const btn = document.querySelector('.copy-btn');
      btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
      btn.classList.add('copied');
      setTimeout(() => {
        btn.innerHTML = '<i class="far fa-copy"></i> Copy Command';
        btn.classList.remove('copied');
      }, 2000);
    });
  }