<?php
/**
 * Base template for Email sending
 */

$subject = $args['subject'] ?? '';
$content = $args['content'] ?? '';
if ( empty( $content ) || empty( $subject ) ) {
	return;
}
$project_name   = $args['site_name'] ?? null;
$project_domain = $args['domain'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php echo esc_attr( $subject ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
       style="background-color:#f4f6f8; margin:0; padding:0;">
	<tr>
		<td align="center" style="padding:32px 16px;">

			<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
			       style="max-width:640px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 32px rgba(15, 23, 42, 0.08);">

				<tr>
					<td style="padding:36px 32px 20px 32px;">
						<div style="font-size:16px; line-height:26px; color:#4b5563; margin-top:14px;">
							<?php echo wp_kses_post( $content ); ?>
						</div>
					</td>
				</tr>

				<!-- Contact Card -->
				<tr>
					<td style="padding:0 32px 32px 32px;">
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
						       style="border-top:1px solid #e5e7eb;">
							<tr>
								<td style="padding-top:24px;">
									<div style="font-size:15px; line-height:24px; color:#374151;">
										<?php esc_html_e( 'Best regards,' , 'east-property' ); ?><br>
										<strong><?php echo esc_attr( $project_name ) . ' '; ?><?php esc_html_e( 'Team' , 'east-property' ); ?></strong>
									</div>
									<div style="font-size:14px; line-height:22px; color:#6b7280; margin-top:10px;">
										<?php esc_html_e( 'Support:' , 'east-property' ); ?>
										<a href="mailto:support@<?php echo esc_attr( $project_domain ); ?>"
										   style="color:#111827; text-decoration:underline;">support@<?php echo esc_attr( $project_domain ); ?></a>
									</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<!-- Footer -->
				<tr>
					<td style="background-color:#f8fafc; padding:22px 32px; border-top:1px solid #e5e7eb;">
						<div style="font-size:12px; line-height:20px; color:#94a3b8;">
							<?php esc_html_e( 'If this was not you, please ignore this message or contact us.' , 'east-property' ); ?>
						</div>
					</td>
				</tr>

			</table>

		</td>
	</tr>
</table>

</body>
</html>
