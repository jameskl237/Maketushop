/** Helpers partagés par les écrans de commande, portefeuille et remboursement. */

export const ORDER_STATUS = {
    pending: { label: 'En attente de paiement', variant: 'secondary' },
    in_progress: { label: 'En cours', variant: 'default' },
    delivered: { label: 'Livrée', variant: 'outline' },
    cancelled: { label: 'Annulée', variant: 'destructive' },
};

export const PAYMENT_STATUS = {
    pending: { label: 'En attente', variant: 'secondary' },
    success: { label: 'Réussi', variant: 'default' },
    failed: { label: 'Échoué', variant: 'destructive' },
};

export const REQUEST_STATUS = {
    pending: { label: 'En attente', variant: 'secondary' },
    approved: { label: 'Approuvée', variant: 'default' },
    paid: { label: 'Versée', variant: 'outline' },
    refunded: { label: 'Remboursée', variant: 'outline' },
    rejected: { label: 'Refusée', variant: 'destructive' },
};

/** Montants en FCFA : entiers, séparateur d'espace insécable. */
export function money(amount) {
    const value = Number(amount ?? 0);
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value) + ' FCFA';
}

export function datetime(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

export function date(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
    });
}

/** « dans 3 jours », « il y a 2 heures » — pour les échéances d'annulation. */
export function relative(value) {
    if (!value) return '—';
    const diff = new Date(value).getTime() - Date.now();
    const rtf = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });
    const units = [
        ['day', 86400000],
        ['hour', 3600000],
        ['minute', 60000],
    ];

    for (const [unit, ms] of units) {
        if (Math.abs(diff) >= ms || unit === 'minute') {
            return rtf.format(Math.round(diff / ms), unit);
        }
    }
    return '—';
}

export function statusMeta(map, status) {
    return map[status] ?? { label: status ?? '—', variant: 'secondary' };
}
