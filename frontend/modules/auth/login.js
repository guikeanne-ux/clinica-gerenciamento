import { authService } from '../../core/auth/auth-service.js';
import { navigate } from '../../core/router/router.js';
import { applyFieldErrors, clearFieldErrors } from '../../core/components/form-errors.js';

export default {
  mount(container) {
    container.innerHTML = `
      <div class="login-page">
        <div class="login-card">
          <div class="login-brand">
            <div class="login-brand__mark">C</div>
            <div class="login-brand__name">ClinicaGest</div>
            <div class="login-brand__sub">Acesse com seu login da clínica</div>
          </div>

          <form id="login-form">
            <div class="field" style="margin-bottom: 0.85rem;">
              <label for="login-input">Login</label>
              <input id="login-input" name="login" class="input" placeholder="Seu usuário" required autocomplete="username" />
            </div>
            <div class="field" style="margin-bottom: 1.25rem;">
              <label for="login-password">Senha</label>
              <input id="login-password" name="password" type="password" class="input" placeholder="••••••••" required autocomplete="current-password" />
            </div>
            <div id="login-error" style="margin-bottom: 0.75rem;"></div>
            <button type="submit" class="btn btn-primary btn-lg" id="login-btn" style="width: 100%;">
              Entrar
            </button>
          </form>
        </div>
      </div>`;

    const form = document.getElementById('login-form');
    const btn  = document.getElementById('login-btn');
    const err  = document.getElementById('login-error');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearFieldErrors(form);
      err.innerHTML = '';
      btn.disabled = true;
      btn.textContent = 'Entrando…';

      const data = Object.fromEntries(new FormData(form));
      const res  = await authService.login(data.login, data.password);

      if (res.success) {
        window.toast?.success('Login realizado com sucesso.');
        navigate('/dashboard', true);
        return;
      }

      applyFieldErrors(form, res.errors || []);
      err.innerHTML = `<div class="field-error">${res.message || 'Credenciais inválidas.'}</div>`;
      btn.disabled = false;
      btn.textContent = 'Entrar';
    });
  },

  unmount() {},
};
