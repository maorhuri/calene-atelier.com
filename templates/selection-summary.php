<?php
/**
 * Selection Summary Template
 * Shows the user's current selection summary
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="decor-selection-summary">
    <div class="selection-summary-inner">
        <h3 class="selection-summary-title">Your Selection Summary</h3>
        
        <div class="selection-summary-content">
            <div class="selection-items">
                <!-- Size Selection -->
                <div class="selection-item selection-size" data-selection="size">
                    <span class="selection-label">Size:</span>
                    <span class="selection-value">
                        <a href="#" class="make-selection-link">Make Selection</a>
                    </span>
                    <span class="selection-check" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                </div>
                
                <!-- Fabric Selection -->
                <div class="selection-item selection-fabric" data-selection="fabric">
                    <span class="selection-label">Fabric and Color:</span>
                    <span class="selection-value">
                        <a href="#" class="make-selection-link">Make Selection</a>
                    </span>
                    <span class="selection-check" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                </div>
            </div>
            
            <div class="selection-preview">
                <img src="" alt="" class="selection-preview-image">
            </div>
        </div>
        
        <!-- Fabric Details in Summary -->
        <div class="selection-fabric-details" style="display: none;">
            <div class="fabric-detail-row">
                <span class="detail-label">Content:</span>
                <span class="detail-value fabric-content-value"></span>
            </div>
            <div class="fabric-detail-row">
                <span class="detail-label">Rub Count:</span>
                <span class="detail-value rub-count-value"></span>
            </div>
            <div class="fabric-detail-row">
                <span class="detail-label">Price Tier:</span>
                <span class="detail-value price-tier-value"></span>
            </div>
            <div class="fabric-detail-row">
                <span class="detail-label">Care:</span>
                <span class="detail-value care-value"></span>
            </div>
        </div>
        
        <!-- Price Summary -->
        <div class="selection-price-summary">
            <div class="price-row base-price">
                <span class="price-label">Base Price:</span>
                <span class="price-value"><?php echo $product->get_price_html(); ?></span>
            </div>
            <div class="price-row fabric-upcharge" style="display: none;">
                <span class="price-label">Fabric Upcharge:</span>
                <span class="price-value"></span>
            </div>
            <div class="price-row total-price">
                <span class="price-label">Total:</span>
                <span class="price-value total-price-value"></span>
            </div>
        </div>
    </div>
</div>
