import { useEffect, useRef, useState } from 'react';

/**
 * Bouton de chat IA en bas à gauche, branché sur le webhook n8n
 * (N8N_CHAT_WEBHOOK), comme sur l'ancien site. Monté une seule fois au
 * niveau racine (voir app.tsx) pour apparaître sur toutes les pages, quel
 * que soit le layout.
 *
 * Composant 100% maison (React + fetch), plutôt que le widget officiel
 * @n8n/chat : ce dernier embarque Vue + son écosystème (~250 Ko), et sa
 * dernière version dépend même d'@n8n/design-system (tiptap, element-plus,
 * jusqu'à un module natif isolated-vm nécessitant une compilation C++) —
 * fragile à installer et disproportionné pour une simple bulle de chat.
 * Ici : quelques Ko, look 100% intégré au design SlapIA, zéro dépendance
 * supplémentaire.
 *
 * Contrat d'appel identique à celui du widget officiel n8n (Chat Trigger),
 * pour rester compatible avec le workflow n8n déjà en place côté serveur,
 * sans rien changer côté n8n :
 *   POST {webhookUrl}
 *   { action: "sendMessage", sessionId: "<uuid persistant>", chatInput: "<message>" }
 * Réponse attendue : { output } ou { text } (les deux formes que renvoie
 * couramment un nœud "Chat Trigger" / "Respond to Webhook" n8n).
 */

interface ChatMessage {
  id: string;
  sender: 'user' | 'bot';
  text: string;
}

const SESSION_STORAGE_KEY = 'slapia_chat_session_id';

function getOrCreateSessionId(): string {
  try {
    const existing = window.localStorage.getItem(SESSION_STORAGE_KEY);
    if (existing) return existing;
    const id = typeof crypto.randomUUID === 'function'
      ? crypto.randomUUID()
      : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    window.localStorage.setItem(SESSION_STORAGE_KEY, id);
    return id;
  } catch {
    // localStorage indisponible (navigation privée stricte, etc.) : la
    // session ne survit pas à un rechargement, mais le chat reste utilisable.
    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
  }
}

export default function ChatWidget({ webhookUrl }: { webhookUrl: string }) {
  const [open, setOpen] = useState(false);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState('');
  const [sending, setSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const sessionIdRef = useRef<string>('');
  const listRef = useRef<HTMLDivElement>(null);

  if (!sessionIdRef.current) {
    sessionIdRef.current = getOrCreateSessionId();
  }

  useEffect(() => {
    listRef.current?.scrollTo({ top: listRef.current.scrollHeight, behavior: 'smooth' });
  }, [messages, sending]);

  async function sendMessage(e: React.FormEvent) {
    e.preventDefault();
    const text = input.trim();
    if (!text || sending) return;

    setMessages((prev) => [...prev, { id: crypto.randomUUID?.() ?? String(Date.now()), sender: 'user', text }]);
    setInput('');
    setSending(true);
    setError(null);

    try {
      const res = await fetch(webhookUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'sendMessage',
          sessionId: sessionIdRef.current,
          chatInput: text,
        }),
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json().catch(() => null);
      const reply: string =
        (Array.isArray(data) ? data[0]?.output ?? data[0]?.text : data?.output ?? data?.text) ??
        'Désolé, je n\'ai pas de réponse pour le moment.';

      setMessages((prev) => [...prev, { id: crypto.randomUUID?.() ?? String(Date.now()), sender: 'bot', text: reply }]);
    } catch {
      setError('La connexion à l\'agent a échoué. Réessayez dans un instant.');
    } finally {
      setSending(false);
    }
  }

  return (
    <div className="slapia-chat">
      {open && (
        <div className="slapia-chat-window" role="dialog" aria-label="Assistant SlapIA">
          <div className="slapia-chat-header">
            <div>
              <p className="slapia-chat-title">Besoin d'aide ?</p>
              <p className="slapia-chat-subtitle">Notre agent IA vous répond</p>
            </div>
            <button
              type="button"
              className="slapia-chat-close"
              onClick={() => setOpen(false)}
              aria-label="Fermer le chat"
            >
              ×
            </button>
          </div>

          <div className="slapia-chat-messages" ref={listRef}>
            {messages.length === 0 && (
              <p className="slapia-chat-empty">Posez votre question, l'agent SlapIA vous répond en quelques instants.</p>
            )}
            {messages.map((m) => (
              <div key={m.id} className={`slapia-chat-bubble slapia-chat-bubble--${m.sender}`}>
                {m.text}
              </div>
            ))}
            {sending && (
              <div className="slapia-chat-bubble slapia-chat-bubble--bot slapia-chat-bubble--typing">
                <span />
                <span />
                <span />
              </div>
            )}
            {error && <p className="slapia-chat-error">{error}</p>}
          </div>

          <form className="slapia-chat-form" onSubmit={sendMessage}>
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder="Écrivez votre message..."
              aria-label="Votre message"
              disabled={sending}
            />
            <button type="submit" disabled={sending || !input.trim()} aria-label="Envoyer">
              ➤
            </button>
          </form>
        </div>
      )}

      <button
        type="button"
        className="slapia-chat-toggle"
        onClick={() => setOpen((v) => !v)}
        aria-label={open ? 'Fermer le chat' : 'Ouvrir le chat avec notre agent IA'}
      >
        {open ? '×' : '💬'}
      </button>

      <style>{`
        .slapia-chat {
          position: fixed;
          left: 1.25rem;
          bottom: 1.25rem;
          z-index: 9999;
          font-family: var(--font-body, 'IBM Plex Sans', sans-serif);
        }
        .slapia-chat-toggle {
          width: 58px;
          height: 58px;
          border-radius: 50%;
          border: none;
          cursor: pointer;
          background: linear-gradient(135deg, var(--signal, #B36FE0), var(--signal-deep, #9147C4));
          color: var(--on-accent, #1A1024);
          font-size: 1.5rem;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 8px 24px rgba(122, 63, 135, 0.35);
          transition: transform 0.15s ease;
        }
        .slapia-chat-toggle:hover { transform: scale(1.06); }
        .slapia-chat-toggle:active { transform: scale(0.96); }

        .slapia-chat-window {
          position: absolute;
          bottom: 72px;
          left: 0;
          width: min(360px, calc(100vw - 2.5rem));
          height: min(520px, calc(100vh - 140px));
          background: var(--white, #fff);
          border: 1px solid var(--line, #E1DCEB);
          border-radius: 16px;
          box-shadow: 0 20px 48px rgba(23, 19, 32, 0.22);
          display: flex;
          flex-direction: column;
          overflow: hidden;
          animation: slapia-chat-pop 0.15s ease;
        }
        @keyframes slapia-chat-pop {
          from { opacity: 0; transform: translateY(8px) scale(0.98); }
          to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .slapia-chat-header {
          background: var(--ink, #171320);
          color: var(--white, #fff);
          padding: 0.9rem 1rem;
          display: flex;
          align-items: flex-start;
          justify-content: space-between;
          gap: 0.5rem;
        }
        .slapia-chat-title { margin: 0; font-weight: 600; font-size: 0.95rem; }
        .slapia-chat-subtitle { margin: 0.15rem 0 0; font-size: 0.78rem; opacity: 0.75; }
        .slapia-chat-close {
          background: none;
          border: none;
          color: inherit;
          font-size: 1.3rem;
          line-height: 1;
          cursor: pointer;
          padding: 0;
        }

        .slapia-chat-messages {
          flex: 1;
          overflow-y: auto;
          padding: 0.9rem;
          display: flex;
          flex-direction: column;
          gap: 0.5rem;
          background: var(--mist, #F5F2FA);
        }
        .slapia-chat-empty {
          margin: auto 0;
          text-align: center;
          color: var(--ink-fade, #726A82);
          font-size: 0.85rem;
        }
        .slapia-chat-bubble {
          max-width: 82%;
          padding: 0.55rem 0.75rem;
          border-radius: 14px;
          font-size: 0.88rem;
          line-height: 1.45;
          white-space: pre-wrap;
          word-break: break-word;
        }
        .slapia-chat-bubble--user {
          align-self: flex-end;
          background: var(--signal, #B36FE0);
          color: var(--on-accent, #1A1024);
          border-bottom-right-radius: 4px;
        }
        .slapia-chat-bubble--bot {
          align-self: flex-start;
          background: var(--white, #fff);
          color: var(--ink, #171320);
          border: 1px solid var(--line, #E1DCEB);
          border-bottom-left-radius: 4px;
        }
        .slapia-chat-bubble--typing {
          display: flex;
          gap: 4px;
          align-items: center;
          padding: 0.7rem 0.9rem;
        }
        .slapia-chat-bubble--typing span {
          width: 6px;
          height: 6px;
          border-radius: 50%;
          background: var(--ink-fade, #726A82);
          animation: slapia-chat-typing 1s infinite ease-in-out;
        }
        .slapia-chat-bubble--typing span:nth-child(2) { animation-delay: 0.15s; }
        .slapia-chat-bubble--typing span:nth-child(3) { animation-delay: 0.3s; }
        @keyframes slapia-chat-typing {
          0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
          30% { opacity: 1; transform: translateY(-3px); }
        }
        .slapia-chat-error {
          color: var(--danger, #C6432E);
          font-size: 0.8rem;
          margin: 0;
        }

        .slapia-chat-form {
          display: flex;
          gap: 0.5rem;
          padding: 0.7rem;
          border-top: 1px solid var(--line, #E1DCEB);
          background: var(--white, #fff);
        }
        .slapia-chat-form input {
          flex: 1;
          border: 1px solid var(--line, #E1DCEB);
          border-radius: 10px;
          padding: 0.55rem 0.7rem;
          font-size: 0.88rem;
          font-family: inherit;
          outline: none;
        }
        .slapia-chat-form input:focus {
          border-color: var(--signal, #B36FE0);
        }
        .slapia-chat-form button {
          width: 38px;
          height: 38px;
          border-radius: 10px;
          border: none;
          background: var(--signal, #B36FE0);
          color: var(--on-accent, #1A1024);
          cursor: pointer;
          font-size: 1rem;
          flex-shrink: 0;
        }
        .slapia-chat-form button:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }

        @media (max-width: 420px) {
          .slapia-chat { left: 0.75rem; bottom: 0.75rem; }
        }
      `}</style>
    </div>
  );
}
