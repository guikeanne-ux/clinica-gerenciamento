import { navigate } from '../../core/router/router.js';

export default {
  mount(container) {
    container.innerHTML = `
      <div class="error-page">
        <div class="error-page__code">403</div>
        <div class="error-page__title">Acesso negado</div>
        <p class="error-page__desc">Você não tem permissão para acessar esta área. Entre em contato com o administrador do sistema.</p>
        <button class="btn btn-primary btn-md" id="btn-go-home">Ir para o início</button>
      </div>`;
    document.getElementById('btn-go-home')?.addEventListener('click', () => navigate('/dashboard'));
  },
  unmount() {},
};
