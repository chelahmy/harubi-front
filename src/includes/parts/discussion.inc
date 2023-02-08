	<!-- Discussion -->
	<div id="discussion">
<?php if (isset($discussion_forward) && $discussion_forward === TRUE) : ?>
		<div class="post-entry">
			<div id="quote_view"></div>
			<form>
				<div class="mb-3">
					<textarea class="form-control" rows="3" id="new_post" aria-describedby="new_postHelp"></textarea>
					<div id="new_postHelp" class="form-text">Your statement.</div>
				</div>
			</form>
			<div id="attachment_files"></div>
			<progress id="attachment_progress" style="display: none;"></progress><br/>
			<button class="btn btn-primary" id="forward_btn">Forward</button>
		</div>		
<?php else : ?>
		<hr/>
		<div class="px-4 text-center">
			<h3 class="display-8">Discussion</h3>
			<button class="btn btn-primary" id="load_earlier_btn" style="display: none;">Load earlier</button>
		</div>
		<hr/>
		<div id="posts"></div>
		<div class="px-4 text-center">
			<button class="btn btn-primary" id="load_newer_btn" style="display: none;">Load newer</button>
			<br/>
		</div>
		<div class="post-entry">
			<div id="quote_view"></div>
			<form>
				<div class="mb-3">
					<textarea class="form-control" rows="3" id="new_post" aria-describedby="new_postHelp"></textarea>
					<div id="new_postHelp" class="form-text">Your statement.</div>
				</div>
			</form>
			<div id="attachment_files"></div>
			<progress id="attachment_progress" style="display: none;"></progress><br/>
			<button class="btn btn-primary" id="post_btn">Post</button>
			<button class="btn btn-secondary" id="attachment_btn" data-bs-toggle="modal" data-bs-target="#attachment">Attachment</button>
		</div>
		
		<!-- Modal Attachment -->
		<div class="modal fade" id="attachment" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h1 class="modal-title fs-5" id="staticBackdropLabel">Attachment</h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<form method='post' action='' enctype='multipart/form-data'> 
							<input type="file" name="files[]" id="files" multiple>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" id="attach_btn" data-bs-dismiss="modal">Attach</button>
						<button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
					</div>
				</div>
			</div>
		</div>
<?php endif; ?>
	</div>
	<!-- End of Discussion -->

