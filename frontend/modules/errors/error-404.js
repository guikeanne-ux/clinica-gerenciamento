import { navigate } from '../../core/router/router.js';

export default {
  mount(container) {
    container.innerHTML = `
      <div class="error-page">
        <div class="error-page__code">404</div>
        <div class="error-page__title">Página não encontrada</div>
        <p class="error-page__desc">A página que você procura não existe ou foi movida. Verifique o endereço e tente novamente.</p>
        <button class="btn btn-primary btn-md" id="btn-go-home-404">Ir para o início</button>
      </div>`;
    document.getElementById('btn-go-home-404')?.addEventListener('click', () => navigate('/dashboard'));
  },
  unmount() {},
};
