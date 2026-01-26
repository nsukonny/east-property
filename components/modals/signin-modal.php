<div class="modal-wrapper signin-modal" data-modal-id="signin-modal">
    <div class="modal">
        <div class="modal-info">
            <div class="modal-title">
                <h3>
                    Sign in
                </h3>
                <button class="modal-close" data-modal-close aria-label="Close">
                    <img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
                </button>
            </div>
            <form>
                <fieldset>
                    <label for="email">
                        Email
                        <input type="email" id="email" required>
                    </label>
                    <label for="password">
                        Password
                        <input type="password" id="password" required>
                    </label>
                </fieldset>
                <div class="submit-group between">
                    <button class="button forgot" data-modal-open="forgot-modal">
                        Forgot password?
                    </button>
                    <button class="button orange sm" type="submit">
                        Create account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>