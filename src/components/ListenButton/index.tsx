import type { Translations } from '../../constants/translations';

interface ListenButtonProps {
  onClick: () => void;
  translations: Translations;
  bgColor: string;
}

export const ListenButton = ({ onClick, translations, bgColor }: ListenButtonProps) => {
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onClick();
    }
  };

  return (
    <div
      className="echoads-listen-btn-container"
      role="button"
      aria-label={translations.listenToArticleAria}
      tabIndex={0}
      onClick={onClick}
      onKeyDown={handleKeyDown}
    >
      <button
        className="echoads-listen-btn"
        style={{ background: bgColor }}
        tabIndex={-1}
      >
        <svg
          className="echoads-listen-icon"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M16.2111 11.1056L9.73666 7.86833C8.93878 7.46939 8 8.04958 8 8.94164V15.0584C8 15.9504 8.93878 16.5306 9.73666 16.1317L16.2111 12.8944C16.9482 12.5259 16.9482 11.4741 16.2111 11.1056Z"
            fill="white"
            stroke="white"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      </button>
      <span className="echoads-listen-text">{translations.listenToArticle}</span>
    </div>
  );
};
