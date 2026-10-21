const observers = new WeakMap()

function resize(element) {
  element.style.height = 'auto'
  const style = getComputedStyle(element)
  const borders = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth)
  element.style.height = `${element.scrollHeight + borders}px`
}

// Keep saved notes, typing and viewport changes fully visible without inner scrolling.
export default {
  mounted(element) {
    element.style.overflowY = 'hidden'
    element.style.resize = 'none'
    element.addEventListener('input', onInput)
    let lastWidth = -1
    const observer = new ResizeObserver(([entry]) => {
      if (entry.contentRect.width === lastWidth) return
      lastWidth = entry.contentRect.width
      resize(element)
    })
    observers.set(element, observer)
    observer.observe(element)
    resize(element)
  },
  updated: resize,
  unmounted(element) {
    element.removeEventListener('input', onInput)
    observers.get(element)?.disconnect()
    observers.delete(element)
  },
}

function onInput(event) {
  resize(event.target)
}
