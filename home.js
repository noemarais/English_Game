document.addEventListener('DOMContentLoaded', () => {
    /* ---------------------------------------------------------
       NAV ROUE BAS → STATS
    --------------------------------------------------------- */
    const homeWheel = document.getElementById('homeWheel');
    if (homeWheel) {
        homeWheel.addEventListener('click', () => {
            homeWheel.classList.add('rotating');
            setTimeout(() => {
                window.location.href = 'stats.php';
            }, 520);
        });
    }

    /* ---------------------------------------------------------
       AVATAR HEADER → STATS
    --------------------------------------------------------- */
    const goToStats = document.getElementById('goToStats');
    if (goToStats) {
        goToStats.addEventListener('click', () => {
            window.location.href = 'stats.php';
        });
    }

    /* ---------------------------------------------------------
       BOUTONS CRÉER / REJOINDRE UNE PARTIE
    --------------------------------------------------------- */
    const btnCreateGame = document.getElementById('btnCreateGame');
    const btnJoinGame = document.getElementById('btnJoinGame');

    if (btnCreateGame) {
        btnCreateGame.addEventListener('click', () => {
            window.location.href = 'create_game.php';
        });
    }

    if (btnJoinGame) {
        btnJoinGame.addEventListener('click', () => {
            window.location.href = 'join_game.php';
        });
    }

    /* ---------------------------------------------------------
       BOUTON "INVITER" SUR UNE CARTE AMI → create_game.php?friend_id=...
    --------------------------------------------------------- */
    document.querySelectorAll('.friend-invite-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const friendId = btn.getAttribute('data-friend-id');
            if (!friendId) return;
            window.location.href = 'create_game.php?friend_id=' + encodeURIComponent(friendId);
        });
    });

    /* ---------------------------------------------------------
       MODAL AJOUT D'AMI
       (on laisse le submit du form se faire en POST classique)
    --------------------------------------------------------- */
    const btnAddFriend = document.getElementById('btnAddFriend');
    const modalAddFriend = document.getElementById('modalAddFriend');
    const btnCloseModal = document.getElementById('btnCloseModal');

    if (btnAddFriend && modalAddFriend) {
        btnAddFriend.addEventListener('click', () => {
            modalAddFriend.style.display = 'flex';
        });
    }

    if (btnCloseModal && modalAddFriend) {
        btnCloseModal.addEventListener('click', () => {
            modalAddFriend.style.display = 'none';
        });
    }

    if (modalAddFriend) {
        modalAddFriend.addEventListener('click', (e) => {
            if (e.target === modalAddFriend) {
                modalAddFriend.style.display = 'none';
            }
        });
    }

    /* ---------------------------------------------------------
       🔥 WEBSOCKET HOME → INVITATIONS EN LIVE
    --------------------------------------------------------- */
    const currentUserId = document.body.getAttribute('data-user-id');

    if (currentUserId) {
        let ws;
        try {
            const WS_BASE = 'wss://noe-marais.mds-angers.yt/ws';

            ws = new WebSocket(
                WS_BASE
                + '?game_id=' + gameId
                + '&user_id=' + userId
                + '&name=' + encodeURIComponent(playerName)
            );
        } catch (e) {
            console.warn('WebSocket non disponible sur la home', e);
        }

        if (ws) {
            ws.addEventListener('message', (event) => {
                let data;
                try {
                    data = JSON.parse(event.data);
                } catch (e) {
                    return;
                }

                // Quand un host envoie game_invite_created vers cet utilisateur
                if (data.type === 'game_invite_created' &&
                    String(data.invited_id) === String(currentUserId)) {
                    refreshGameInvites();
                }
            });
        }
    }

    /* ---------------------------------------------------------
       REFRESH DES INVITATIONS VIA AJAX
    --------------------------------------------------------- */
    function refreshGameInvites() {
        fetch('game_invites_poll.php', {
            method: 'GET',
            credentials: 'same-origin'
        })
            .then(r => r.json())
            .then(invites => {
                renderGameInvites(invites);
            })
            .catch(err => console.error(err));
    }

    function renderGameInvites(invites) {
        let wrapper = document.querySelector('.game-invites-wrapper');

        // Pas d'invitations
        if (!invites || invites.length === 0) {
            if (wrapper) {
                wrapper.innerHTML = '';
            }
            return;
        }

        // Si le wrapper n'existe pas (au cas où), on le crée et on le met après le bloc demandes d'amis
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'game-invites-wrapper';

            const app = document.querySelector('.app');
            const pendingBox = document.querySelector('.pending-box');
            const gameActions = document.querySelector('.game-actions');

            if (app) {
                if (pendingBox) {
                    app.insertBefore(wrapper, pendingBox.nextSibling);
                } else if (gameActions) {
                    app.insertBefore(wrapper, gameActions);
                } else {
                    app.appendChild(wrapper);
                }
            } else {
                document.body.appendChild(wrapper);
            }
        }

        let html = `
            <section class="game-invites">
                <h3 class="game-invites-title title-font">Invitations de partie</h3>
        `;

        invites.forEach(inv => {
            const gameName = inv.nom && inv.nom.length ? inv.nom : 'Partie Surf It';

            html += `
                <article class="game-invite-card">
                    <div class="game-invite-main text-font">
                        <div class="game-invite-host">
                            ${escapeHtml(inv.host_name)} t a invité à jouer
                        </div>
                        <div class="game-invite-name">
                            Partie : ${escapeHtml(gameName)}
                        </div>
                        <div class="game-invite-code">
                            Code : <strong>${escapeHtml(inv.code)}</strong>
                        </div>
                    </div>
                    <div class="game-invite-actions">
                        <form method="POST" action="game_invite_action.php">
                            <input type="hidden" name="invite_id" value="${inv.id}">
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="game-invite-accept text-font">Rejoindre</button>
                        </form>
                        <form method="POST" action="game_invite_action.php">
                            <input type="hidden" name="invite_id" value="${inv.id}">
                            <input type="hidden" name="action" value="decline">
                            <button type="submit" class="game-invite-decline text-font">Refuser</button>
                        </form>
                    </div>
                </article>
            `;
        });

        html += `</section>`;
        wrapper.innerHTML = html;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
