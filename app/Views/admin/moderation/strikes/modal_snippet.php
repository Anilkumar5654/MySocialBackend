<div class="modal fade" id="addStrikeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            
            <form action="<?= base_url('admin/moderation/strikes/report_decision') ?>" method="post" id="strikeForm">
                <?= csrf_field() ?>
                
                <input type="hidden" name="content_type" id="strike_content_type">
                <input type="hidden" name="content_id" id="strike_content_id">
                <input type="hidden" name="channel_id" id="strike_channel_id">
                <input type="hidden" name="decision" id="strike_decision_type" value="ISSUE_STRIKE">

                <div class="modal-header border-bottom bg-light py-3">
                    <h5 class="modal-title font-weight-bold text-dark" id="modalMainTitle">
                        <i class="fas fa-gavel mr-2 text-primary"></i> Issue Penalty
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body p-4">
                    <div id="policyAlert" class="p-3 rounded mb-4 small bg-light border d-flex align-items-center">
                        <i class="fas fa-shield-alt text-primary mr-3" style="font-size: 1.2rem;"></i>
                        <div id="policyDescription" style="line-height: 1.4;">
                            <div class="text-dark font-weight-bold">Standard Strike Policy</div>
                            <div class="text-muted">Penalty points will be deducted and content will be blocked instantly.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 form-group">
                            <label class="small text-muted font-weight-bold text-uppercase">Moderation Action</label>
                            <select name="type" id="moderationActionType" class="form-control" style="border-radius: 8px; height: 45px; font-weight: 700; border: 2px solid var(--primary-blue);" required onchange="handleActionFlow(this.value)">
                                <option value="STRIKE">🔴 Formal Strike (Block + Points)</option>
                                <option value="WARNING">🟡 Warning Only (Alert Only)</option>
                                <option value="CLAIM">💰 Revenue Claim (Share Earnings)</option>
                            </select>
                        </div>
                    </div>

                    <div id="pointsSection" class="form-group mb-3">
                        <label class="small text-danger font-weight-bold text-uppercase">Penalty Points (Trust Score)</label>
                        <input type="number" name="severity_points" class="form-control font-weight-bold" value="10" min="1" max="100" style="border-radius: 8px; height: 42px;">
                    </div>

                    <div id="copyrightSection" class="p-3 mb-3 rounded" style="background: rgba(253, 57, 122, 0.05); border: 1px solid rgba(253, 57, 122, 0.2); display: none;">
                        <label class="small text-primary font-weight-bold mb-2 text-uppercase d-block">
                            <i class="fas fa-link mr-1"></i> Original Content ID (Reference)
                        </label>
                        <input type="number" name="original_video_id" class="form-control font-weight-bold" 
                               placeholder="Enter original video ID..." style="border-radius: 8px; height: 40px;">
                        <small class="text-muted mt-2 d-block" style="font-size: 10px;">
                            * Required to link revenue sharing or blacklist hashes.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted font-weight-bold text-uppercase">Violation Category</label>
                        <select name="reason" id="strikeReason" class="form-control" style="border-radius: 8px; height: 42px;">
                            <option value="Copyright Infringement">Copyright Infringement</option>
                            <option value="Nudity or Sexual">Nudity / Sexual</option>
                            <option value="Violent Content">Violent / Graphic</option>
                            <option value="Hate Speech">Hate Speech</option>
                            <option value="Other">Other Policy Breach</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="small text-muted font-weight-bold text-uppercase">Admin Note for Creator</label>
                        <textarea name="description" class="form-control" rows="3" 
                        placeholder="Why is this action being taken?" style="resize: none; border-radius: 10px;" required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top py-3">
                    <button type="button" class="btn btn-link text-muted font-weight-bold btn-sm" data-dismiss="modal">CANCEL</button>
                    <button type="submit" id="submitBtn" class="btn btn-danger font-weight-bold px-4 shadow-sm" style="border-radius: 8px;">
                        <i class="fas fa-check-circle mr-1"></i> APPLY ACTION
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * ✅ SMART FLOW HANDLER
 * Switch UI based on Strike, Warning, or Claim
 */
function handleActionFlow(val) {
    const pointsDiv = document.getElementById('pointsSection');
    const copyDiv = document.getElementById('copyrightSection');
    const policyTitle = document.querySelector('#policyDescription .text-dark');
    const policyText = document.querySelector('#policyDescription .text-muted');
    const submitBtn = document.getElementById('submitBtn');
    const decisionInput = document.getElementById('strike_decision_type');

    // Default Reset
    pointsDiv.style.display = 'none';
    copyDiv.style.display = 'none';
    submitBtn.className = 'btn font-weight-bold px-4 shadow-sm';
    decisionInput.value = 'ISSUE_STRIKE';

    if(val === 'STRIKE') {
        pointsDiv.style.display = 'block';
        copyDiv.style.display = 'block'; // Strike needs origin usually
        policyTitle.innerText = "Formal Strike Issued";
        policyText.innerText = "Content blocked. Trust score will be reduced.";
        submitBtn.classList.add('btn-danger');
        decisionInput.value = 'ISSUE_STRIKE';
    } 
    else if(val === 'WARNING') {
        policyTitle.innerText = "Policy Warning";
        policyText.innerText = "No points deducted. Content remains active but flagged.";
        submitBtn.classList.add('btn-warning');
        decisionInput.value = 'SEND_WARNING';
    } 
    else if(val === 'CLAIM') {
        copyDiv.style.display = 'block';
        policyTitle.innerText = "Revenue Share Claim";
        policyText.innerText = "Earnings will be diverted to the original creator.";
        submitBtn.classList.add('btn-primary');
        decisionInput.value = 'REVENUE_CLAIM';
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    handleActionFlow(document.getElementById('moderationActionType').value);
});
</script>
