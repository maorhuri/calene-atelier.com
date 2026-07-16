<?php
/**
 * Calene Trade Application & Partnership Agreement
 * Professional trade program registration form
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="trade-application-wrap">
    
    <!-- Page Header -->
    <div class="trade-application-header">
        <h1>Calene Atelier — Trade Application & Partnership Agreement</h1>
        <p class="header-description">Calene Atelier is a curated trade program designed to support our trade partners with access to custom, made-to-order collections and a seamless sourcing experience.</p>
    </div>
    
    <form id="trade-application-form" class="trade-application-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('dealer_registration', 'dealer_nonce'); ?>
        
        <!-- Section 1: Applicant Information -->
        <div class="form-section">
            <h2 class="section-title">Section 1 — Applicant Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" name="email" id="email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number <span class="required">*</span></label>
                    <input type="tel" name="phone" id="phone" required>
                </div>
            </div>
        </div>
        
        <!-- Section 2: Business Details -->
        <div class="form-section">
            <h2 class="section-title">Section 2 — Business Details</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="company">Company Name <span class="required">*</span></label>
                    <input type="text" name="company" id="company" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="address_1">Business Address <span class="required">*</span></label>
                    <input type="text" name="address_1" id="address_1" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="city_state_zip">City / State / Zip Code <span class="required">*</span></label>
                    <input type="text" name="city_state_zip" id="city_state_zip" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="website">Website / Portfolio <span class="optional">(optional)</span></label>
                    <input type="url" name="website" id="website">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="social_media">Social Media <span class="optional">(optional)</span></label>
                    <input type="text" name="social_media" id="social_media">
                </div>
            </div>
            
            <!-- Trade Verification Documents -->
            <div class="documents-subsection">
                <h3 class="subsection-title">Trade Verification Documents</h3>
                <p class="subsection-description">Please provide documentation to verify your trade status.</p>
                
                <div class="form-row">
                    <div class="form-group file-upload-group">
                        <label for="resale_certificate">Resale Certificate <span class="required">*</span></label>
                        <p class="field-hint">(Required for tax-exempt purchasing)</p>
                        <input type="file" name="resale_certificate" id="resale_certificate" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group file-upload-group">
                        <label for="business_license">Business License <span class="optional">(optional)</span></label>
                        <input type="file" name="business_license" id="business_license" accept=".jpg,.jpeg,.png,.gif,.pdf">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section 3: Professional Status -->
        <div class="form-section">
            <h2 class="section-title">Section 3 — Professional Status</h2>
            
            <div class="form-row">
                <div class="form-group checkbox-list">
                    <div class="checkbox-item">
                        <label>
                            <input type="checkbox" name="professional_status[]" value="interior_designer">
                            Interior Designer
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <label>
                            <input type="checkbox" name="professional_status[]" value="architect">
                            Architect
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <label>
                            <input type="checkbox" name="professional_status[]" value="developer">
                            Developer
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <label>
                            <input type="checkbox" name="professional_status[]" value="retailer">
                            Retailer
                        </label>
                    </div>
                    <div class="checkbox-item other-option">
                        <label>
                            <input type="checkbox" name="professional_status[]" value="other" id="status_other_checkbox">
                            Other:
                        </label>
                        <input type="text" name="professional_status_other" id="professional_status_other" class="other-text-field">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section 4: Project & Sourcing Profile -->
        <div class="form-section">
            <h2 class="section-title">Section 4 — Project & Sourcing Profile</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="group-label">Typical Project Type</label>
                    <div class="checkbox-list horizontal">
                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="project_type[]" value="residential">
                                Residential
                            </label>
                        </div>
                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="project_type[]" value="commercial">
                                Commercial
                            </label>
                        </div>
                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="project_type[]" value="hospitality">
                                Hospitality
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="group-label">Estimated Annual Purchasing Volume</label>
                    <div class="checkbox-list">
                        <div class="checkbox-item">
                            <label>
                                <input type="radio" name="annual_volume" value="under_50k">
                                Under $50,000
                            </label>
                        </div>
                        <div class="checkbox-item">
                            <label>
                                <input type="radio" name="annual_volume" value="50k_150k">
                                $50,000 – $150,000
                            </label>
                        </div>
                        <div class="checkbox-item">
                            <label>
                                <input type="radio" name="annual_volume" value="150k_plus">
                                $150,000+
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section 5: Trade Partnership Terms -->
        <div class="form-section">
            <h2 class="section-title">Section 5 — Trade Partnership Terms</h2>
            
            <div class="terms-accordion">
                <button type="button" class="terms-toggle" aria-expanded="false">
                    <span>Trade Partnership Terms</span>
                    <span class="toggle-icon">▼</span>
                </button>
                <div class="terms-content" style="display: none;">
                    <div class="terms-scroll-box">
                        <h3>Calene Atelier — Trade Partnership Terms</h3>
                        <p>Calene Atelier is a curated trade program designed to support designers with access to custom, made-to-order collections and a seamless sourcing experience.</p>
                        
                        <h4>Eligibility & Use</h4>
                        <p>Membership in the Calene Atelier Trade Program is reserved for approved design professionals. Access to trade pricing and services is exclusive to the registered member and may not be transferred or extended to third parties.</p>
                        
                        <h4>Ordering & Payment</h4>
                        <p>All orders must be placed directly by the Trade Member. Payment is processed through the member only; we do not accept payments from end clients. Production and delivery timelines begin once payment is confirmed.</p>
                        
                        <h4>Pricing & Benefits</h4>
                        <p>Trade pricing and program benefits apply exclusively to approved members and are not retroactive to purchases made prior to acceptance into the program.</p>
                        
                        <h4>Shipping & Returns</h4>
                        <p>Due to the made-to-order nature of many Calene Atelier pieces, all sales are final unless otherwise specified. Approved returns, if applicable, are subject to return shipping fees and handling charges.</p>
                        
                        <h4>Use of Products</h4>
                        <p>Products are intended for use within the scope of the member's professional design services for individual clients. Calene Atelier reserves the right to limit or restrict use that does not align with the brand's positioning.</p>
                        
                        <h4>Intellectual Property</h4>
                        <p>All imagery, designs, and brand materials remain the exclusive property of Calene Atelier. Approved members may use provided materials solely for client presentations and project-related purposes, unless otherwise authorized in writing.</p>
                        
                        <h4>Communications</h4>
                        <p>By applying to the program, you agree to receive communications related to your membership, including updates, product launches, and relevant brand information. You may opt out of marketing communications at any time.</p>
                        
                        <h4>Membership Status</h4>
                        <p>Calene Atelier reserves the right to approve, decline, or revoke membership at its discretion to maintain the integrity and positioning of the program.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section 6: Certification & Agreement -->
        <div class="form-section">
            <h2 class="section-title">Section 6 — Certification & Agreement</h2>
            
            <div class="certification-box">
                <h4>Certification</h4>
                <p>I certify that the above statements are true, complete, and correct, and that no material information has been omitted. I make these statements and issue this exemption certificate with the knowledge that this document provides evidence that state and local sales or use taxes do not apply to a transaction or transactions for which I tendered this document and that willfully issuing this document with the intent to evade any such tax may constitute a felony or other crime under New York State Law, punishable by a substantial fine and a possible jail sentence. I understand that this document is required to be filed with, and delivered to, the vendor as agent for the Tax Department for the purpose of Tax Law section 1838 and is deemed a document required to be filed with the Tax Department for the purpose of prosecution of offenses. I also understand that the Tax Department is authorized to investigate the validity of tax exclusions or exemptions claimed and the accuracy of any information entered on this document.</p>
            </div>
            
            <div class="agreement-checkboxes">
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="agree_partnership_terms" id="agree_partnership_terms" required>
                            I acknowledge and agree to the Calene Atelier Trade Partnership Terms and confirm my participation as a Trade Partner.
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="agree_terms" id="agree_terms" required>
                            I certify that the above information is accurate and agree to all applicable terms and conditions.
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section 7: Signature -->
        <div class="form-section">
            <h2 class="section-title">Section 7 — Signature</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="print_name">Name (Print) <span class="required">*</span></label>
                    <input type="text" name="print_name" id="print_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="signature">Signature <span class="required">*</span></label>
                    <div class="signature-pad-container">
                        <canvas id="signature-pad" class="signature-pad"></canvas>
                        <button type="button" class="clear-signature-btn">Clear Signature</button>
                    </div>
                    <input type="hidden" name="signature" id="signature-data" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="signature_date">Date <span class="required">*</span></label>
                    <input type="date" name="signature_date" id="signature_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
        </div>
        
        <!-- Submit -->
        <div class="form-section form-submit-section">
            <button type="submit" class="trade-submit-btn">Submit Trade Application</button>
        </div>
        
        <div class="form-messages"></div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Terms accordion toggle
    $('.terms-toggle').on('click', function() {
        var $content = $(this).next('.terms-content');
        var isExpanded = $(this).attr('aria-expanded') === 'true';
        
        $(this).attr('aria-expanded', !isExpanded);
        $content.slideToggle(300);
        $(this).find('.toggle-icon').text(isExpanded ? '▼' : '▲');
    });
    
    // Other professional status - enable text field when checked
    $('#status_other_checkbox').on('change', function() {
        $('#professional_status_other').prop('disabled', !this.checked);
        if (this.checked) {
            $('#professional_status_other').focus();
        }
    });
});
</script>
