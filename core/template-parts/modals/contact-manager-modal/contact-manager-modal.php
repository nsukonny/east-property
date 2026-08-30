<?php
$user_location = $_SERVER['REMOTE_ADDR']; //Get it by geoip2/geoip2 or any other method you prefer
?>
<div class="modal-wrapper desc-modal contact-manager-modal" data-modal-id="contact-manager-modal">
    <div class="modal">
        <div class="modal-info">
            <div class="modal-title">
                <h3><?php _e( 'Get a quick consultation' , 'east-property' ); ?></h3>
                <button class="modal-close" data-modal-close aria-label="Close">
                    <img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
                </button>
            </div>

            <div class="ccm-header">
                <p class="ccm-subtitle">
                    <?php _e( 'Choose the most convenient way to connect with our team. Message us on WhatsApp for the fastest
                                        reply, or leave your number and a dedicated specialist will call you back shortly.' , 'east-property' ); ?>
                </p>
            </div>

            <div class="ccm-content">

                <!-- WhatsApp Block - Primary / Recommended -->
                <div class="ccm-block ccm-block-primary">
                    <div class="ccm-recommended-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                        </svg>
                        <?php _e( 'Recommended' , 'east-property' ); ?>
                    </div>
                    <div class="ccm-block-header">
                        <div class="ccm-icon ccm-icon-whatsapp">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <div class="ccm-block-titles">
                            <h4 class="ccm-block-label"><?php _e( 'WhatsApp — fastest reply' , 'east-property' ); ?></h4>
                            <p class="ccm-block-description">
                                <?php _e( 'Best for urgent questions, pricing, and quick consultation.' , 'east-property' ); ?>
                            </p>
                        </div>
                    </div>
                    <a
                            href="<?php echo WHATS_APP_LINK; ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="ccm-btn ccm-btn-primary"
                    >
                        <?php _e( 'Open WhatsApp' , 'east-property' ); ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2">
                            <path d="M7 17L17 7M17 7H7M17 7v10"/>
                        </svg>
                    </a>
                    <small class="ccm-helper"><?php _e( 'We usually reply within 5–10 minutes during working hours.' , 'east-property' ); ?></small>
                </div>

                <!-- Divider -->
                <div class="ccm-divider"><span><?php _e( 'or' , 'east-property' ); ?></span></div>

                <!-- Callback Block - Secondary -->
                <div class="ccm-block ccm-block-secondary">
                    <div class="ccm-block-header">
                        <div class="ccm-icon ccm-icon-phone">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                            </svg>
                        </div>
                        <div class="ccm-block-titles">
                            <h4 class="ccm-block-label"><?php _e( 'Request a callback' , 'east-property' ); ?></h4>
                            <p class="ccm-block-description">
                                <?php _e( 'Leave your phone number and our specialist will contact you shortly.' , 'east-property' ); ?>
                            </p>
                        </div>
                    </div>
                    <form class="ccm-callback-form" id="ccmCallbackForm" name="contact_manager_form" method="post">
                        <div class="ccm-input-wrapper">
                            <input
                                    type="tel"
                                    class="ccm-input"
                                    placeholder="<?php _e( 'Enter your phone number' , 'east-property' ); ?>"
                                    required
                                    aria-label="<?php _e( 'Phone number' , 'east-property' ); ?>"
                                    name="phone_number"
                            />
                            <input type="hidden" name="action" value="contact_manager_callback_form">
                            <input type="hidden" name="user_location" value="<?php echo $user_location; ?>">
                        </div>
                        <label class="ccm-checkbox-wrapper">
                            <input type="checkbox" class="ccm-checkbox" required/>
                            <span class="ccm-checkbox-custom"></span>
                            <span class="ccm-checkbox-label"><?php _e( 'I agree to the processing of my personal data.' , 'east-property' ); ?></span>
                        </label>
                        <button type="submit" class="ccm-btn ccm-btn-secondary">
                            <?php _e( 'Request a callback' , 'east-property' ); ?>
                        </button>
                    </form>
                    <small class="ccm-helper">
                        <?php _e( 'No spam. Only a personal response from our team regarding your request.' , 'east-property' ); ?>
                    </small>
                </div>

            </div>

            <div class="ccm-footer">
                <p class="ccm-trust-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <?php _e( 'Your information is kept strictly confidential' , 'east-property' ); ?>
                </p>
            </div>
        </div>
    </div>
</div>