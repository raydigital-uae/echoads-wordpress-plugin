const PauseIcon = ({ isPlaying }: { isPlaying: boolean }) => {
  return (
    <svg
      className='pause-icon'
      style={{ display: isPlaying ? 'block' : 'none' }}
      width='24'
      height='24'
      viewBox='0 0 24 24'
      fill='none'
      xmlns='http://www.w3.org/2000/svg'
    >
      <rect x='6' y='5' width='4' height='14' rx='1' fill='white' />
      <rect x='14' y='5' width='4' height='14' rx='1' fill='white' />
    </svg>
  )
}

export default PauseIcon
