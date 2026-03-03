import type { Translations } from '../../constants/translations'
import PlayIcon from '../Icons/Play'

interface ListenButtonProps {
  onClick: () => void
  translations: Translations
  bgColor: string
}

export const ListenButton = ({
  onClick,
  translations,
  bgColor,
}: ListenButtonProps) => {
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      onClick()
    }
  }

  return (
    <div
      className='echoads-listen-btn-container'
      role='button'
      aria-label={translations.listenToArticleAria}
      tabIndex={0}
      onClick={onClick}
      onKeyDown={handleKeyDown}
    >
      <button
        className='echoads-listen-btn'
        style={{ background: bgColor }}
        tabIndex={-1}
      >
        <PlayIcon className='echoads-listen-icon' isPlaying={false} />
      </button>

      <span className='echoads-listen-text'>
        {translations.listenToArticle}
      </span>
    </div>
  )
}
