/**
 * Undo/Redo System with Change History
 * 
 * Complete change history tracking:
 * - Add/Delete/Move/Edit field operations
 * - Memory-based history (configurable limit, default 50 steps)
 * - Full state snapshots
 * - Keyboard shortcuts: CTRL+Z (undo), CTRL+Y (redo)
 * - Change timeline visualization
 * - Batch operations support
 * 
 * @package YetAnotherPlugin
 * @since 2.0.0
 */

window.FieldHistory = window.FieldHistory || {};

/**
 * ============================================
 * HISTORY STATE MANAGEMENT
 * ============================================
 */
FieldHistory.config = {
    maxSteps: 50,           // Maximum history steps to keep
    autoSave: true,         // Auto-save on every change
    batchMode: false,       // Batch multiple operations
    batchTimeout: 500       // Batch timeout in ms
};

FieldHistory.state = {
    history: [],            // Array of history states
    currentIndex: -1,       // Current position in history
    batchOps: [],          // Operations in current batch
    batchTimer: null       // Timer for batch timeout
};

/**
 * ============================================
 * CHANGE TRACKING
 * ============================================
 */

/**
 * Record a field addition
 */
FieldHistory.recordAdd = function(field, position = 'end') {
    const change = {
        type: 'add',
        timestamp: Date.now(),
        field: JSON.parse(JSON.stringify(field)),
        position: position,
        description: `Added field: ${field.label}`,
        beforeState: JSON.parse(JSON.stringify(window.yapBuilder.schema.fields))
    };

    this._addToHistory(change);
};

/**
 * Record a field deletion
 */
FieldHistory.recordDelete = function(fieldId) {
    const field = window.yapBuilder.schema.fields.find(f => f.id === fieldId);
    if (!field) return;

    const change = {
        type: 'delete',
        timestamp: Date.now(),
        field: JSON.parse(JSON.stringify(field)),
        fieldId: fieldId,
        description: `Deleted field: ${field.label}`,
        beforeState: JSON.parse(JSON.stringify(window.yapBuilder.schema.fields))
    };

    this._addToHistory(change);
};

/**
 * Record a field move
 */
FieldHistory.recordMove = function(fieldId, fromIndex, toIndex) {
    const field = window.yapBuilder.schema.fields[toIndex];
    if (!field) return;

    const change = {
        type: 'move',
        timestamp: Date.now(),
        fieldId: fieldId,
        fromIndex: fromIndex,
        toIndex: toIndex,
        description: `Moved field: ${field.label}`,
        beforeState: JSON.parse(JSON.stringify(window.yapBuilder.schema.fields))
    };

    this._addToHistory(change);
};

/**
 * Record field settings edit
 */
FieldHistory.recordEdit = function(fieldId, oldSettings, newSettings) {
    const field = window.yapBuilder.schema.fields.find(f => f.id === fieldId);
    if (!field) return;

    const change = {
        type: 'edit',
        timestamp: Date.now(),
        fieldId: fieldId,
        fieldLabel: field.label,
        oldSettings: oldSettings,
        newSettings: newSettings,
        description: `Edited field: ${field.label}`,
        beforeState: JSON.parse(JSON.stringify(window.yapBuilder.schema.fields))
    };

    this._addToHistory(change);
};

/**
 * Start batch operation
 */
FieldHistory.startBatch = function(description = 'Batch operation') {
    this.state.batchMode = true;
    this.state.batchOps = [];
    this.state.batchDescription = description;
    
    // Clear existing timeout
    if (this.state.batchTimer) {
        clearTimeout(this.state.batchTimer);
    }

    // Auto-commit batch after timeout
    this.state.batchTimer = setTimeout(() => {
        this.commitBatch();
    }, this.config.batchTimeout);
};

/**
 * Commit batch operation
 */
FieldHistory.commitBatch = function() {
    if (!this.state.batchMode || this.state.batchOps.length === 0) {
        return { success: false, error: 'No batch operations to commit' };
    }

    const change = {
        type: 'batch',
        timestamp: Date.now(),
        operations: this.state.batchOps,
        description: this.state.batchDescription || `Batch: ${this.state.batchOps.length} operations`,
        beforeState: JSON.parse(JSON.stringify(window.yapBuilder.schema.fields))
    };

    this.state.batchMode = false;
    this.state.batchOps = [];

    if (this.state.batchTimer) {
        clearTimeout(this.state.batchTimer);
        this.state.batchTimer = null;
    }

    this._addToHistory(change);
    return { success: true, operations: change.operations.length };
};

/**
 * ============================================
 * HISTORY MANAGEMENT
 * ============================================
 */

/**
 * Internal method to add change to history
 */
FieldHistory._addToHistory = function(change) {
    // Remove redo history if we're at the end
    if (this.state.currentIndex < this.state.history.length - 1) {
        this.state.history = this.state.history.slice(0, this.state.currentIndex + 1);
    }

    // Add change
    this.state.history.push(change);
    this.state.currentIndex++;

    // Limit history size
    if (this.state.history.length > this.config.maxSteps) {
        this.state.history.shift();
        this.state.currentIndex--;
    }

    console.log(`📝 History: +1 (${this.state.currentIndex + 1}/${this.state.history.length})`);
};

/**
 * Undo last change
 */
FieldHistory.undo = function() {
    if (this.state.currentIndex <= 0) {
        console.log('❌ Nothing to undo');
        return { success: false, error: 'Nothing to undo' };
    }

    this.state.currentIndex--;
    const change = this.state.history[this.state.currentIndex];

    this._applyChange(change, true);

    console.log(`↶ Undo: ${change.description}`);
    return { success: true, change: change };
};

/**
 * Redo last undone change
 */
FieldHistory.redo = function() {
    if (this.state.currentIndex >= this.state.history.length - 1) {
        console.log('❌ Nothing to redo');
        return { success: false, error: 'Nothing to redo' };
    }

    this.state.currentIndex++;
    const change = this.state.history[this.state.currentIndex];

    this._applyChange(change, false);

    console.log(`↷ Redo: ${change.description}`);
    return { success: true, change: change };
};

/**
 * Apply a change (undo or redo)
 */
FieldHistory._applyChange = function(change, isUndo) {
    if (!window.yapBuilder || !window.yapBuilder.schema) {
        console.error('Schema not initialized');
        return;
    }

    if (isUndo && !change.beforeState) {
        console.error('No before state available');
        return;
    }

    // Restore before state
    window.yapBuilder.schema.fields = JSON.parse(JSON.stringify(change.beforeState));

    // Trigger UI update if available
    if (window.yapBuilder.updateUI) {
        window.yapBuilder.updateUI();
    }

    // Trigger custom event
    document.dispatchEvent(new CustomEvent('yap:schema-changed', {
        detail: { change: change, isUndo: isUndo }
    }));
};

/**
 * Get current position in history
 */
FieldHistory.getCurrentPosition = function() {
    return {
        current: this.state.currentIndex + 1,
        total: this.state.history.length,
        canUndo: this.state.currentIndex > 0,
        canRedo: this.state.currentIndex < this.state.history.length - 1
    };
};

/**
 * Get change timeline
 */
FieldHistory.getTimeline = function(limit = 20) {
    const timeline = this.state.history.map((change, idx) => ({
        index: idx,
        isCurrent: idx === this.state.currentIndex,
        type: change.type,
        description: change.description,
        timestamp: change.timestamp,
        timeAgo: this._formatTimeAgo(change.timestamp)
    }));

    return timeline.slice(Math.max(0, timeline.length - limit));
};

/**
 * Clear history
 */
FieldHistory.clear = function() {
    this.state.history = [];
    this.state.currentIndex = -1;
    console.log('🗑️ History cleared');
    return { success: true };
};

/**
 * Get history stats
 */
FieldHistory.getStats = function() {
    const adds = this.state.history.filter(c => c.type === 'add').length;
    const deletes = this.state.history.filter(c => c.type === 'delete').length;
    const moves = this.state.history.filter(c => c.type === 'move').length;
    const edits = this.state.history.filter(c => c.type === 'edit').length;
    const batches = this.state.history.filter(c => c.type === 'batch').length;

    return {
        total: this.state.history.length,
        adds,
        deletes,
        moves,
        edits,
        batches,
        maxSteps: this.config.maxSteps
    };
};

/**
 * ============================================
 * KEYBOARD SHORTCUTS
 * ============================================
 */

/**
 * Setup keyboard shortcuts
 */
FieldHistory.setupKeyboardShortcuts = function() {
    document.addEventListener('keydown', (e) => {
        // Undo: CTRL+Z or CMD+Z
        if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
            e.preventDefault();
            FieldHistory.undo();
        }

        // Redo: CTRL+Y or CTRL+SHIFT+Z or CMD+SHIFT+Z
        if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
            e.preventDefault();
            FieldHistory.redo();
        }
    });

    console.log('⌨️ Keyboard shortcuts enabled: CTRL+Z (undo), CTRL+Y (redo)');
};

/**
 * ============================================
 * UI COMPONENTS
 * ============================================
 */

/**
 * Render history timeline
 */
FieldHistory.renderTimeline = function() {
    const timeline = this.getTimeline();
    let html = '<div class="history-timeline">';

    timeline.forEach((item) => {
        const current = item.isCurrent ? 'current' : '';
        const icon = this._getChangeIcon(item.type);
        
        html += `
            <div class="timeline-item ${current}" data-index="${item.index}">
                <span class="timeline-icon">${icon}</span>
                <span class="timeline-desc">${item.description}</span>
                <span class="timeline-time">${item.timeAgo}</span>
            </div>
        `;
    });

    html += '</div>';
    return html;
};

/**
 * Render undo/redo buttons
 */
FieldHistory.renderControls = function() {
    const pos = this.getCurrentPosition();
    const undoDisabled = !pos.canUndo ? 'disabled' : '';
    const redoDisabled = !pos.canRedo ? 'disabled' : '';

    let html = `
        <div class="history-controls">
            <button class="history-btn undo-btn ${undoDisabled}" data-action="undo" title="Undo (CTRL+Z)">
                ↶ Undo
            </button>
            <button class="history-btn redo-btn ${redoDisabled}" data-action="redo" title="Redo (CTRL+Y)">
                ↷ Redo
            </button>
            <span class="history-count">${pos.current}/${pos.total}</span>
        </div>
    `;

    return html;
};

/**
 * Render history panel
 */
FieldHistory.renderPanel = function() {
    const stats = this.getStats();
    const timeline = this.getTimeline(10);

    let html = '<div class="history-panel">';
    html += '<h3>Change History</h3>';
    
    // Stats
    html += `
        <div class="history-stats">
            <div class="stat">
                <span class="stat-value">${stats.total}</span>
                <span class="stat-label">Total Changes</span>
            </div>
            <div class="stat">
                <span class="stat-value">+${stats.adds}</span>
                <span class="stat-label">Added</span>
            </div>
            <div class="stat">
                <span class="stat-value">−${stats.deletes}</span>
                <span class="stat-label">Deleted</span>
            </div>
            <div class="stat">
                <span class="stat-value">⟷${stats.moves}</span>
                <span class="stat-label">Moved</span>
            </div>
        </div>
    `;

    // Timeline
    html += '<div class="history-list">';
    timeline.forEach(item => {
        const icon = this._getChangeIcon(item.type);
        const current = item.isCurrent ? 'current' : '';
        html += `
            <div class="history-entry ${current}">
                <span class="entry-icon">${icon}</span>
                <span class="entry-desc">${item.description}</span>
                <span class="entry-time">${item.timeAgo}</span>
            </div>
        `;
    });
    html += '</div>';

    html += '</div>';
    return html;
};

/**
 * ============================================
 * UTILITIES
 * ============================================
 */

/**
 * Get icon for change type
 */
FieldHistory._getChangeIcon = function(type) {
    const icons = {
        add: '➕',
        delete: '🗑️',
        move: '⟷',
        edit: '✎',
        batch: '📦'
    };
    return icons[type] || '•';
};

/**
 * Format time ago
 */
FieldHistory._formatTimeAgo = function(timestamp) {
    const now = Date.now();
    const diff = now - timestamp;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    if (seconds < 60) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return new Date(timestamp).toLocaleDateString();
};

/**
 * ============================================
 * INTEGRATION WITH FIELD STABILIZATION
 * ============================================
 */

/**
 * Hook into field operations
 */
FieldHistory.hookIntoFieldOps = function() {
    // Store original methods if not already stored
    if (!window._yapOriginalFieldOps) {
        window._yapOriginalFieldOps = {
            duplicateField: FieldStabilization.duplicateField,
            createStableField: FieldStabilization.createStableField
        };
    }

    // Wrap duplicateField
    FieldStabilization.duplicateField = function(field, includeSubFields) {
        const result = window._yapOriginalFieldOps.duplicateField.call(this, field, includeSubFields);
        if (result.success) {
            FieldHistory.recordAdd(result.field);
        }
        return result;
    };

    // Wrap createStableField
    FieldStabilization.createStableField = function(type, settings) {
        const result = window._yapOriginalFieldOps.createStableField.call(this, type, settings);
        FieldHistory.recordAdd(result);
        return result;
    };

    console.log('🔗 Field operations hooked into history');
};

/**
 * ============================================
 * INITIALIZATION
 * ============================================
 */

/**
 * Initialize history system
 */
FieldHistory.init = function() {
    this.clear();
    this.setupKeyboardShortcuts();
    this.hookIntoFieldOps();
    
    console.log('%c✅ Field History System initialized', 'color: #0f0; font-weight: bold;');
    console.log('Shortcuts: CTRL+Z (undo), CTRL+Y (redo)');
};

// Auto-initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        FieldHistory.init();
    });
} else {
    FieldHistory.init();
}

console.log('%c✅ Field History & Undo/Redo System loaded', 'color: #0f0; font-weight: bold;');
console.log('Use: FieldHistory.undo(), FieldHistory.redo()');
