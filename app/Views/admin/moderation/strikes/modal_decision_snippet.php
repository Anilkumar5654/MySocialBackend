<div class="modal fade" id="decisionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            
            <form action="<?= base_url('admin/moderation/strikes/report_decision') ?>" method="post" id="decisionForm">
                <?= csrf_field() ?>
                
                <input type="hidden" name="claim_id" id="modal_claim_id">
                <input type="hidden" name="decision" id="modal_decision">
                
                <input type="hidden" name="content_id" id="modal_content_id">
                <input type="hidden" name="content_type" id="modal_content_type">
                <input type="hidden" name="channel_id" id="modal_channel_id">

                <div class="modal-body text-center p-4">
                    <div id="modal_icon_box" class="mb-3" style="font-size: 48px;"></div>
                    
                    <h5 class="font-weight-bold text-dark mb-2" id="modal_title">Confirm Action</h5>
                    <p class="small text-muted mb-4" id="modal_text"></p>

                    <div id="original_video_div" class="mb-3 text-left">
                        <label class="small font-weight-bold text-uppercase" style="color: var(--primary-blue); font-size: 10px;">Original Content ID</label>
                        <input type="number" name="original_video_id" id="modal_original_video_id" class="form-control font-weight-bold" style="border-radius: 8px; border: 1px solid var(--border-soft);">
                        <small class="text-muted d-block mt-1" style="font-size: 9px;">Pre-filled from case details.</small>
                    </div>
                    
                    <div id="severity_input_div" style="display:none;" class="mb-4 text-left">
                        <label class="small font-weight-bold text-uppercase" style="color: var(--accent-red); font-size: 10px;">Strike Penalty Points</label>
                        <input type="number" name="severity_points" class="form-control text-center font-weight-bold" value="10" min="1" style="border-radius: 8px;">
                    </div>

                    <button type="submit" id="modal_submit" class="btn btn-block font-weight-bold text-white py-3 shadow-sm" style="border-radius: 10px; border: none;"></button>
                    
                    <button type="button" class="btn btn-link btn-sm text-muted mt-2 font-weight-bold" data-dismiss="modal">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * ✅ Upgraded Function: Accepts Auto-Data from View.php
 */
function handleClaim(id, action, autoData = {}) {
    $('#modal_claim_id').val(id);
    $('#modal_decision').val(action);
    
    // 🔥 Fill Hidden Fields automatically
    $('#modal_content_id').val(autoData.content_id || '');
    $('#modal_content_type').val(autoData.content_type || '');
    $('#modal_channel_id').val(autoData.channel_id || '');
    $('#modal_original_video_id').val(autoData.original_video_id || '');

    const btn = $('#modal_submit');
    const icon = $('#modal_icon_box');
    const title = $('#modal_title');
    const text = $('#modal_text');
    const sevDiv = $('#severity_input_div');
    const origDiv = $('#original_video_div');
    
    sevDiv.hide();
    origDiv.show(); // Always show to confirm ID

    if(action === 'ISSUE_STRIKE') {
        title.text('Issue Copyright Strike');
        text.text('Video will be removed and points deducted.');
        icon.html('<i class="fas fa-gavel" style="color: var(--accent-red);"></i>');
        btn.css('background', 'var(--accent-red)').text('CONFIRM STRIKE');
        sevDiv.show(); 
    } 
    else if(action === 'REVENUE_CLAIM') {
        title.text('Approve Revenue Claim');
        text.text('Monetization will be shared with the original creator.');
        icon.html('<i class="fas fa-handshake" style="color: var(--primary-blue);"></i>');
        btn.css('background', 'var(--primary-blue)').text('APPROVE CLAIM');
    }
    else if(action === 'REMOVE_CONTENT') {
        title.text('Block Content');
        text.text('Content will be hidden (No Penalty Points).');
        icon.html('<i class="fas fa-eye-slash text-warning"></i>');
        btn.css('background', 'orange').text('CONFIRM BLOCK');
    } 
    else if(action === 'REJECT') {
        title.text('Dismiss Takedown');
        text.text('Content will remain live. Reporter may be penalized.');
        icon.html('<i class="fas fa-times-circle text-muted"></i>');
        btn.css('background', '#abb3ba').text('DISMISS');
        origDiv.hide();
    }

    $('#decisionModal').modal('show');
}
</script>
