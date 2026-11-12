const initializeInbox = () => {
    const inboxList = document.getElementById('inbox-list');

    if (!inboxList) {
        return;
    }

    const notifications = Array.from(inboxList.querySelectorAll('[data-inbox-item]'));
    const filterButtons = Array.from(document.querySelectorAll('[data-inbox-filter]'));
    const searchInput = document.querySelector('[data-inbox-search]');
    const emptyState = document.querySelector('[data-inbox-empty]');
    const emptyTitle = document.querySelector('[data-inbox-empty-title]');
    const emptyDescription = document.querySelector('[data-inbox-empty-description]');
    const markAllButton = document.querySelector('[data-inbox-mark-all]');
    const statusMessage = document.querySelector('[data-inbox-status]');
    const sidebarUnreadBadge = document.querySelector('[data-inbox-sidebar-count]');
    let activeFilter = 'all';

    const getCounts = () => {
        const activeNotifications = notifications.filter((notification) => notification.dataset.archived !== 'true');
        const unreadNotifications = activeNotifications.filter((notification) => notification.dataset.read !== 'true');

        return {
            all: activeNotifications.length,
            unread: unreadNotifications.length,
            archived: notifications.length - activeNotifications.length,
        };
    };

    const updateNotification = (notification) => {
        const isRead = notification.dataset.read === 'true';
        const isArchived = notification.dataset.archived === 'true';
        const unreadIndicator = notification.querySelector('[data-inbox-unread-indicator]');
        const readButton = notification.querySelector('[data-inbox-toggle-read]');
        const archiveButton = notification.querySelector('[data-inbox-toggle-archive]');
        const archiveIcon = archiveButton?.querySelector('[data-inbox-archive-icon]');
        const restoreIcon = archiveButton?.querySelector('[data-inbox-restore-icon]');

        notification.classList.toggle('bg-indigo-50/30', !isRead);
        notification.classList.toggle('bg-white', isRead);
        unreadIndicator?.classList.toggle('hidden', isRead);
        readButton?.setAttribute('aria-label', isRead ? 'Mark notification as unread' : 'Mark notification as read');
        readButton?.setAttribute('title', isRead ? 'Mark as unread' : 'Mark as read');
        readButton?.classList.toggle('bg-emerald-50', isRead);
        readButton?.classList.toggle('text-emerald-600', isRead);
        readButton?.classList.toggle('text-slate-400', !isRead);
        archiveButton?.setAttribute('aria-label', isArchived ? 'Restore notification' : 'Archive notification');
        archiveButton?.setAttribute('title', isArchived ? 'Restore' : 'Archive');
        archiveIcon?.classList.toggle('hidden', isArchived);
        restoreIcon?.classList.toggle('hidden', !isArchived);
    };

    const updateInbox = () => {
        const searchTerm = searchInput instanceof HTMLInputElement
            ? searchInput.value.trim().toLocaleLowerCase()
            : '';
        let visibleCount = 0;

        notifications.forEach((notification) => {
            const isRead = notification.dataset.read === 'true';
            const isArchived = notification.dataset.archived === 'true';
            const matchesFilter = activeFilter === 'archived'
                ? isArchived
                : !isArchived && (activeFilter !== 'unread' || !isRead);
            const searchableText = (notification.dataset.search ?? notification.textContent).toLocaleLowerCase();
            const isVisible = matchesFilter && searchableText.includes(searchTerm);

            notification.classList.toggle('hidden', !isVisible);
            notification.setAttribute('aria-hidden', String(!isVisible));
            visibleCount += Number(isVisible);
        });

        inboxList.querySelectorAll('[data-inbox-group]').forEach((group) => {
            const hasVisibleNotifications = group.querySelector('[data-inbox-item]:not(.hidden)') !== null;
            group.classList.toggle('hidden', !hasVisibleNotifications);
        });

        const counts = getCounts();

        Object.entries(counts).forEach(([filter, count]) => {
            document.querySelectorAll(`[data-inbox-count="${filter}"]`).forEach((countElement) => {
                countElement.textContent = String(count);
            });
        });

        if (sidebarUnreadBadge) {
            sidebarUnreadBadge.textContent = String(counts.unread);
            sidebarUnreadBadge.setAttribute('aria-label', `${counts.unread} unread notifications`);
            sidebarUnreadBadge.classList.toggle('hidden', counts.unread === 0);
        }

        if (markAllButton instanceof HTMLButtonElement) {
            markAllButton.disabled = counts.unread === 0;
            markAllButton.classList.toggle('opacity-50', markAllButton.disabled);
            markAllButton.classList.toggle('cursor-not-allowed', markAllButton.disabled);
        }

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
            emptyState.classList.toggle('flex', visibleCount === 0);
        }

        if (visibleCount === 0 && emptyTitle && emptyDescription) {
            const hasSearch = searchTerm.length > 0;

            if (activeFilter === 'archived') {
                emptyTitle.textContent = hasSearch ? 'No archived updates match your search' : 'No archived updates';
                emptyDescription.textContent = hasSearch
                    ? 'Try another search to find an archived update.'
                    : 'Notifications you archive will appear here.';
            } else if (activeFilter === 'unread' && !hasSearch) {
                emptyTitle.textContent = 'You’re all caught up';
                emptyDescription.textContent = 'There are no unread updates right now.';
            } else {
                emptyTitle.textContent = 'No matching updates';
                emptyDescription.textContent = 'Try another search or filter.';
            }
        }
    };

    notifications.forEach(updateNotification);
    updateInbox();

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.inboxFilter ?? 'all';

            filterButtons.forEach((filterButton) => {
                const isActive = filterButton === button;

                filterButton.setAttribute('aria-pressed', String(isActive));
                filterButton.classList.toggle('bg-white', isActive);
                filterButton.classList.toggle('text-slate-900', isActive);
                filterButton.classList.toggle('shadow-sm', isActive);
                filterButton.classList.toggle('text-slate-500', !isActive);
            });

            updateInbox();
        });
    });

    searchInput?.addEventListener('input', updateInbox);

    inboxList.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const notification = event.target.closest('[data-inbox-item]');

        if (!notification) {
            return;
        }

        if (event.target.closest('[data-inbox-toggle-read]')) {
            notification.dataset.read = notification.dataset.read === 'true' ? 'false' : 'true';
            updateNotification(notification);
            updateInbox();
            statusMessage.textContent = notification.dataset.read === 'true'
                ? 'Notification marked as read.'
                : 'Notification marked as unread.';

            return;
        }

        if (event.target.closest('[data-inbox-toggle-archive]')) {
            notification.dataset.archived = notification.dataset.archived === 'true' ? 'false' : 'true';
            updateNotification(notification);
            updateInbox();
            statusMessage.textContent = notification.dataset.archived === 'true'
                ? 'Notification archived.'
                : 'Notification restored to your inbox.';
        }
    });

    markAllButton?.addEventListener('click', () => {
        notifications.forEach((notification) => {
            if (notification.dataset.archived !== 'true') {
                notification.dataset.read = 'true';
                updateNotification(notification);
            }
        });

        updateInbox();
        statusMessage.textContent = 'All inbox notifications marked as read.';
    });
};

initializeInbox();
