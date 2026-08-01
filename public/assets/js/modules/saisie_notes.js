/* ============================================================
   Fichier : public/assets/js/modules/saisie_notes.js
   Rôle    : Gestion de la grille de saisie des notes
   Système : Bulletin congolais — Navigation clavier, validation, sauvegarde
   Version : 2.0 — Refonte complète
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const notesGridTable    = document.getElementById('notesGridTable');
    const btnSaveNotes      = document.getElementById('btnSaveNotes');
    const btnToutVider      = document.getElementById('btnToutVider');
    const saisieSummary     = document.getElementById('saisieSummary');
    const flashMessage      = document.getElementById('flashMessage');

    if (!notesGridTable) return;

    const evaluationId = btnSaveNotes ? parseInt(btnSaveNotes.dataset.evaluationId) : 0;
    let modifiedCells = new Set();
    let autoSaveInterval = null;
    let currentCell = null;

    // ============================================================
    // 2. GESTION DES CELLULES
    // ============================================================

    /**
     * Récupère toutes les cellules de note dans l'ordre de navigation
     */
    function getAllNoteInputs() {
        return Array.from(notesGridTable.querySelectorAll('.note-input'));
    }

    /**
     * Récupère l'index d'une cellule dans la liste
     */
    function getCellIndex(cell) {
        const allInputs = getAllNoteInputs();
        return allInputs.indexOf(cell);
    }

    /**
     * Récupère la cellule suivante (ou précédente)
     */
    function getNextCell(current, direction = 1) {
        const allInputs = getAllNoteInputs();
        const currentIndex = getCellIndex(current);
        const nextIndex = currentIndex + direction;

        if (nextIndex >= 0 && nextIndex < allInputs.length) {
            return allInputs[nextIndex];
        }
        return null;
    }

    /**
     * Récupère la cellule au-dessus/en-dessous (même colonne)
     */
    function getVerticalCell(current, direction = -1) {
        const allInputs = getAllNoteInputs();
        const currentIndex = getCellIndex(current);
        const row = current.closest('tr');
        if (!row) return null;

        const rows = Array.from(notesGridTable.querySelectorAll('tbody tr'));
        const currentRowIndex = rows.indexOf(row);
        const cellsInRow = row.querySelectorAll('.note-input');
        const cellIndexInRow = Array.from(cellsInRow).indexOf(current);

        const nextRowIndex = currentRowIndex + direction;
        if (nextRowIndex >= 0 && nextRowIndex < rows.length) {
            const nextRowCells = rows[nextRowIndex].querySelectorAll('.note-input');
            if (cellIndexInRow < nextRowCells.length) {
                return nextRowCells[cellIndexInRow];
            }
        }
        return null;
    }

    // ============================================================
    // 3. NAVIGATION CLAVIER
    // ============================================================

    notesGridTable.addEventListener('keydown', function(e) {
        const target = e.target;
        if (!target.classList.contains('note-input')) return;

        const key = e.key;

        switch (key) {
            case 'Tab':
                e.preventDefault();
                const nextCell = e.shiftKey ? getNextCell(target, -1) : getNextCell(target, 1);
                if (nextCell) {
                    nextCell.focus();
                    nextCell.select();
                }
                break;

            case 'Enter':
                e.preventDefault();
                const cellBelow = getVerticalCell(target, 1);
                if (cellBelow) {
                    cellBelow.focus();
                    cellBelow.select();
                }
                break;

            case 'ArrowUp':
                e.preventDefault();
                const cellAbove = getVerticalCell(target, -1);
                if (cellAbove) {
                    cellAbove.focus();
                    cellAbove.select();
                }
                break;

            case 'ArrowDown':
                e.preventDefault();
                const cellBelowArrow = getVerticalCell(target, 1);
                if (cellBelowArrow) {
                    cellBelowArrow.focus();
                    cellBelowArrow.select();
                }
                break;

            case 'ArrowLeft':
                e.preventDefault();
                const prevCell = getNextCell(target, -1);
                if (prevCell) {
                    prevCell.focus();
                    prevCell.select();
                }
                break;

            case 'ArrowRight':
                e.preventDefault();
                const nextCellArrow = getNextCell(target, 1);
                if (nextCellArrow) {
                    nextCellArrow.focus();
                    nextCellArrow.select();
                }
                break;

            case 'Escape':
                e.preventDefault();
                target.value = target.dataset.originalValue || '';
                target.classList.remove('modified');
                modifiedCells.delete(target);
                updateCellState(target);
                target.blur();
                break;
        }
    });

    // ============================================================
    // 4. GESTION DE LA SAISIE
    // ============================================================

    notesGridTable.addEventListener('input', function(e) {
        const target = e.target;
        if (!target.classList.contains('note-input')) return;

        const rawValue = target.value.trim();

        // Si la cellule était en mode "ABS", on réinitialise
        if (target.dataset.originalValue === 'ABS' && rawValue !== 'ABS' && rawValue !== '') {
            target.classList.remove('absent');
            target.style.color = '';
        }

        // Marquer comme modifié si différent de l'original
        if (rawValue !== target.dataset.originalValue) {
            target.classList.add('modified');
            modifiedCells.add(target);
        } else {
            target.classList.remove('modified');
            modifiedCells.delete(target);
        }

        updateCellState(target);
    });

    notesGridTable.addEventListener('focus', function(e) {
        const target = e.target;
        if (!target.classList.contains('note-input')) return;
        currentCell = target;
    }, true);

    notesGridTable.addEventListener('blur', function(e) {
        const target = e.target;
        if (!target.classList.contains('note-input')) return;

        // Validation de la note au blur
        validateNoteCell(target);
    }, true);

    // ============================================================
    // 5. DOUBLE-CLIC = ABSENT
    // ============================================================

    notesGridTable.addEventListener('dblclick', function(e) {
        const target = e.target;
        if (!target.classList.contains('note-input')) return;

        e.preventDefault();

        if (target.value === 'ABS') {
            // Annuler l'absence
            target.value = target.dataset.originalValue !== 'ABS' ? target.dataset.originalValue : '';
            target.classList.remove('absent');
            target.style.color = '';
        } else {
            // Marquer absent
            target.value = 'ABS';
            target.classList.add('absent');
            target.style.color = '#ef4444';
            target.classList.remove('saisie');
        }

        // Marquer comme modifié
        if (target.value !== target.dataset.originalValue) {
            target.classList.add('modified');
            modifiedCells.add(target);
        } else {
            target.classList.remove('modified');
            modifiedCells.delete(target);
        }

        updateCellState(target);

        // Passer à la cellule suivante
        const nextCell = getNextCell(target, 1);
        if (nextCell) {
            nextCell.focus();
            nextCell.select();
        }
    });

    // ============================================================
    // 6. VALIDATION DES NOTES
    // ============================================================

    /**
     * Valide une cellule de note
     */
    function validateNoteCell(cell) {
        const rawValue = cell.value.trim();
        const maxima = parseFloat(cell.dataset.maxima) || 20;

        cell.classList.remove('error');

        if (rawValue === '' || rawValue === 'ABS') {
            return true;
        }

        // Vérifier si c'est un nombre valide
        const noteValue = parseFloat(rawValue.replace(',', '.'));
        if (isNaN(noteValue)) {
            cell.classList.add('error');
            showCellError(cell, 'Valeur invalide');
            return false;
        }

        // Vérifier que la note ne dépasse pas le maxima
        if (noteValue > maxima) {
            cell.classList.add('error');
            showCellError(cell, `Max ${maxima}`);
            return false;
        }

        // Vérifier que la note n'est pas négative
        if (noteValue < 0) {
            cell.classList.add('error');
            showCellError(cell, 'Min 0');
            return false;
        }

        return true;
    }

    /**
     * Affiche une erreur sur une cellule
     */
    function showCellError(cell, message) {
        // Créer un tooltip temporaire
        const tooltip = document.createElement('div');
        tooltip.className = 'cell-error-tooltip';
        tooltip.textContent = message;
        tooltip.style.cssText = `
            position: absolute;
            background: #ef4444;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            z-index: 20;
            pointer-events: none;
            white-space: nowrap;
        `;

        const rect = cell.getBoundingClientRect();
        tooltip.style.top = (rect.bottom + 4) + 'px';
        tooltip.style.left = (rect.left + rect.width / 2) + 'px';
        tooltip.style.transform = 'translateX(-50%)';

        document.body.appendChild(tooltip);

        // Supprimer après 2 secondes
        setTimeout(() => {
            tooltip.style.opacity = '0';
            tooltip.style.transition = 'opacity 0.3s ease';
            setTimeout(() => tooltip.remove(), 300);
        }, 2000);
    }

    /**
     * Met à jour l'état visuel d'une cellule
     */
    function updateCellState(cell) {
        const rawValue = cell.value.trim();

        cell.classList.remove('saisie', 'absent', 'error');

        if (rawValue === 'ABS') {
            cell.classList.add('absent');
            cell.style.color = '#ef4444';
        } else if (rawValue !== '') {
            cell.classList.add('saisie');
            cell.style.color = '';
        } else {
            cell.style.color = '';
        }
    }

    // ============================================================
    // 7. SAUVEGARDE DES NOTES
    // ============================================================

    /**
     * Sauvegarde toutes les notes modifiées
     */
    async function saveNotes(silent = false) {
        if (!evaluationId) {
            if (!silent) showSaveToast('error', 'ID évaluation manquant.');
            return;
        }

        const allInputs = getAllNoteInputs();
        const notesData = [];

        allInputs.forEach(input => {
            const eleveId = parseInt(input.dataset.eleveId);
            const sousMatiereId = parseInt(input.dataset.sousMatiereId);
            const rawValue = input.value.trim();

            if (!eleveId || !sousMatiereId) return;

            let valeur = null;
            let absence = 0;

            if (rawValue === 'ABS') {
                absence = 1;
            } else if (rawValue !== '') {
                valeur = parseFloat(rawValue.replace(',', '.'));
                if (isNaN(valeur)) valeur = null;
            }

            // Ne pas envoyer si pas de changement
            if (rawValue === input.dataset.originalValue && !input.classList.contains('modified')) {
                return;
            }

            notesData.push({
                eleve_id: eleveId,
                sous_matiere_id: sousMatiereId,
                valeur: valeur,
                absence: absence
            });
        });

        if (notesData.length === 0) {
            if (!silent) showSaveToast('info', 'Aucune modification à enregistrer.');
            return;
        }

        // Activer l'état de chargement
        const gridCard = document.getElementById('notesGridCard');
        if (gridCard) gridCard.classList.add('loading');
        if (btnSaveNotes) {
            btnSaveNotes.disabled = true;
            btnSaveNotes.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';
        }

        const formData = new FormData();
        formData.append('action', 'sauvegarderNotes');
        formData.append('evaluation_id', evaluationId);
        formData.append('notes', JSON.stringify(notesData));

        try {
            const response = await fetch('/gestion_scolaire/notes', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Mettre à jour les valeurs originales
                allInputs.forEach(input => {
                    input.dataset.originalValue = input.value.trim();
                    input.classList.remove('modified');
                });
                modifiedCells.clear();

                if (!silent) showSaveToast('success', data.message);
                updateResumeSaisie();
            } else {
                if (!silent) showSaveToast('error', data.message);
            }
        } catch (error) {
            console.error('Erreur sauvegarde:', error);
            if (!silent) showSaveToast('error', 'Erreur de connexion au serveur.');
        } finally {
            if (gridCard) gridCard.classList.remove('loading');
            if (btnSaveNotes) {
                btnSaveNotes.disabled = false;
                btnSaveNotes.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer';
            }
        }
    }

    /**
     * Affiche un toast de sauvegarde
     */
    function showSaveToast(type, message) {
        // Supprimer les anciens toasts
        document.querySelectorAll('.save-toast').forEach(t => t.remove());

        const toast = document.createElement('div');
        toast.className = `save-toast ${type}`;
        const icons = {
            success: 'fa-circle-check',
            error: 'fa-circle-xmark',
            info: 'fa-circle-info'
        };
        toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i> ${message}`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Met à jour le résumé de saisie
     */
    function updateResumeSaisie() {
        if (!saisieSummary) return;

        const allInputs = getAllNoteInputs();
        const summaryData = {};

        allInputs.forEach(input => {
            const smId = input.dataset.sousMatiereId;
            if (!smId) return;

            if (!summaryData[smId]) {
                summaryData[smId] = { total: 0, saisis: 0 };
            }

            summaryData[smId].total++;
            if (input.value.trim() !== '') {
                summaryData[smId].saisis++;
            }
        });

        // Mettre à jour l'affichage
        const summaryItems = saisieSummary.querySelectorAll('.summary-item');
        summaryItems.forEach(item => {
            const countEl = item.querySelector('.summary-item-count');
            const fillEl = item.querySelector('.summary-item-fill');
            // La mise à jour se fait via l'ID de la sous-matière stocké dans un data attribute
            // Simplifié : on recharge la page ou on fait une mise à jour AJAX
        });
    }

    // ============================================================
    // 8. RACCOURCIS CLAVIER GLOBAUX
    // ============================================================

    document.addEventListener('keydown', function(e) {
        // Ctrl+S : Sauvegarder
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveNotes();
        }

        // Ctrl+Z : Annuler la cellule active
        if ((e.ctrlKey || e.metaKey) && e.key === 'z' && currentCell) {
            e.preventDefault();
            currentCell.value = currentCell.dataset.originalValue || '';
            currentCell.classList.remove('modified');
            modifiedCells.delete(currentCell);
            updateCellState(currentCell);
        }
    });

    // ============================================================
    // 9. BOUTON ENREGISTRER
    // ============================================================

    if (btnSaveNotes) {
        btnSaveNotes.addEventListener('click', function(e) {
            e.preventDefault();
            // Valider toutes les cellules avant sauvegarde
            let hasError = false;
            getAllNoteInputs().forEach(input => {
                if (!validateNoteCell(input)) {
                    hasError = true;
                }
            });

            if (hasError) {
                showSaveToast('error', 'Corrigez les erreurs avant d\'enregistrer.');
                return;
            }

            saveNotes();
        });
    }

    // ============================================================
    // 10. BOUTON VIDER
    // ============================================================

    if (btnToutVider) {
        btnToutVider.addEventListener('click', function() {
            const modifiedCount = modifiedCells.size;
            const totalCount = getAllNoteInputs().length;

            if (modifiedCount > 0) {
                showConfirmDialog(
                    `Vous avez <strong>${modifiedCount}</strong> cellule(s) modifiée(s) non sauvegardée(s).<br>Voulez-vous vraiment tout effacer ?`,
                    function(confirmed) {
                        if (confirmed) {
                            viderToutesLesNotes();
                        }
                    },
                    {
                        title: 'Vider toutes les notes',
                        confirmText: 'Tout effacer',
                        confirmClass: 'btn-danger',
                        icon: 'fa-eraser',
                        iconColor: '#ef4444'
                    }
                );
            } else if (totalCount > 0) {
                showConfirmDialog(
                    'Voulez-vous vraiment effacer toutes les notes de cette évaluation ?',
                    function(confirmed) {
                        if (confirmed) {
                            viderToutesLesNotes();
                        }
                    },
                    {
                        title: 'Vider toutes les notes',
                        confirmText: 'Tout effacer',
                        confirmClass: 'btn-danger',
                        icon: 'fa-eraser',
                        iconColor: '#ef4444'
                    }
                );
            }
        });
    }

    function viderToutesLesNotes() {
        getAllNoteInputs().forEach(input => {
            input.value = '';
            input.classList.remove('saisie', 'absent', 'modified', 'error');
            input.style.color = '';
            input.dataset.originalValue = '';
        });
        modifiedCells.clear();
        showSaveToast('info', 'Toutes les notes ont été effacées. Pensez à sauvegarder.');
    }

    // ============================================================
    // 11. AUTO-SAVE (toutes les 30 secondes)
    // ============================================================

    function startAutoSave() {
        stopAutoSave();
        autoSaveInterval = setInterval(() => {
            if (modifiedCells.size > 0) {
                saveNotes(true);
            }
        }, 30000);
    }

    function stopAutoSave() {
        if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
            autoSaveInterval = null;
        }
    }

    // Démarrer l'auto-save
    startAutoSave();

    // Arrêter quand la page est masquée
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAutoSave();
            // Sauvegarder avant de quitter
            if (modifiedCells.size > 0) {
                saveNotes(true);
            }
        } else {
            startAutoSave();
        }
    });

    // ============================================================
    // 12. ALERTE AVANT DE QUITTER LA PAGE
    // ============================================================

    window.addEventListener('beforeunload', function(e) {
        if (modifiedCells.size > 0) {
            // Sauvegarde automatique silencieuse
            saveNotes(true);

            // Message d'avertissement (la plupart des navigateurs ne l'affichent plus)
            const message = 'Vous avez des modifications non sauvegardées.';
            e.returnValue = message;
            return message;
        }
    });

    // ============================================================
    // 13. VALIDATION EN TEMPS RÉEL LORS DE LA FRAPPE
    // ============================================================

    notesGridTable.addEventListener('keyup', function(e) {
        const target = e.target;
        if (!target.classList.contains('note-input')) return;

        // Validation après un court délai
        clearTimeout(target._validationTimeout);
        target._validationTimeout = setTimeout(() => {
            validateNoteCell(target);
        }, 500);
    });

    // ============================================================
    // 14. INITIALISATION
    // ============================================================

    // Focus sur la première cellule vide
    const firstEmptyCell = getAllNoteInputs().find(input => input.value.trim() === '');
    if (firstEmptyCell) {
        setTimeout(() => {
            firstEmptyCell.focus();
            firstEmptyCell.select();
        }, 300);
    }

    console.log('%cSaisie Notes%c initialisée avec succès. %cCtrl+S%c pour sauvegarder.',
        'color: #2c3e90; font-weight: 700;',
        'color: #64748b;',
        'background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 600;',
        'color: #64748b;');
});