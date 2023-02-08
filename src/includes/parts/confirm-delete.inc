	<!-- Modal Confirm Delete -->
	<div class="modal fade" id="confirm_delete" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="staticBackdropLabel"><?= $confirm_delete_title; ?></h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
				<div class="modal-body">
					<p><?= $confirm_delete_message; ?></p>
					<p><span id="delete_type"></span>&nbsp;&ndash;&nbsp;<strong id="delete_details"></strong><p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" id="confirmed_delete_btn" data-bs-dismiss="modal">Yes</button>
					<button type="button" class="btn btn-primary" data-bs-dismiss="modal">No</button>
				</div>
			</div>
		</div>
	</div>

