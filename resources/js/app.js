document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.querySelector('[data-sidebar]')
  const backdrop = document.querySelector('[data-sidebar-backdrop]')
  const openButton = document.querySelector('[data-sidebar-open]')
  const sidebarScroll = document.querySelector('[data-sidebar-scroll]')
  const sidebarScrollKey = 'simiva:sidebar-scroll-top'

  const closeSidebar = () => {
    sidebar?.classList.add('-translate-x-full')
    backdrop?.classList.add('pointer-events-none', 'opacity-0')
    backdrop?.classList.remove('opacity-100')
  }

  openButton?.addEventListener('click', () => {
    sidebar?.classList.remove('-translate-x-full')
    backdrop?.classList.remove('pointer-events-none', 'opacity-0')
    backdrop?.classList.add('opacity-100')
  })

  backdrop?.addEventListener('click', closeSidebar)

  if (sidebarScroll) {
    const saveSidebarScroll = () => {
      sessionStorage.setItem(sidebarScrollKey, String(sidebarScroll.scrollTop))
    }

    const restoreSidebarScroll = () => {
      const savedScroll = Number(sessionStorage.getItem(sidebarScrollKey))
      if (Number.isFinite(savedScroll)) {
        sidebarScroll.scrollTop = savedScroll
      }
    }

    const keepActiveMenuVisible = () => {
      const activeItem = sidebarScroll.querySelector('[data-sidebar-active]')
      if (!activeItem) return

      const containerRect = sidebarScroll.getBoundingClientRect()
      const activeRect = activeItem.getBoundingClientRect()
      const topOverflow = activeRect.top - containerRect.top
      const bottomOverflow = activeRect.bottom - containerRect.bottom
      const padding = 12

      if (topOverflow < 0) {
        sidebarScroll.scrollTop += topOverflow - padding
      } else if (bottomOverflow > 0) {
        sidebarScroll.scrollTop += bottomOverflow + padding
      }
    }

    sidebarScroll.querySelectorAll('[data-sidebar-details]').forEach((details) => {
      if (details.querySelector('[data-sidebar-active]')) {
        details.open = true
      }

      details.addEventListener('toggle', saveSidebarScroll)
    })

    sidebarScroll.querySelectorAll('a[href]').forEach((link) => {
      link.addEventListener('click', saveSidebarScroll)
    })

    sidebarScroll.addEventListener('scroll', saveSidebarScroll, { passive: true })
    window.addEventListener('beforeunload', saveSidebarScroll)

    restoreSidebarScroll()
    requestAnimationFrame(keepActiveMenuVisible)
  }

  const openModal = (id) => {
    const modal = document.querySelector(`[data-modal="${id}"]`)
    if (!modal) return
    modal.classList.remove('hidden')
    modal.classList.add('grid')
    lockPageScroll()
    modal.querySelector('[autofocus], [role="dialog"]')?.focus()
  }

  const closeModal = (modal) => {
    modal?.classList.add('hidden')
    modal?.classList.remove('grid')
    unlockPageScrollWhenIdle()
  }

  const lockPageScroll = () => {
    if (!Object.hasOwn(document.body.dataset, 'modalPreviousOverflow')) {
      document.body.dataset.modalPreviousOverflow = document.body.style.overflow
    }
    document.body.style.overflow = 'hidden'
  }

  const unlockPageScrollWhenIdle = () => {
    const hasOpenModal = Array.from(document.querySelectorAll('[data-modal]')).some((modal) => !modal.classList.contains('hidden'))
    if (hasOpenModal || !Object.hasOwn(document.body.dataset, 'modalPreviousOverflow')) return

    document.body.style.overflow = document.body.dataset.modalPreviousOverflow
    delete document.body.dataset.modalPreviousOverflow
  }

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => openModal(button.dataset.modalOpen))
  })

  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => closeModal(button.closest('[data-modal]')))
  })

  document.querySelectorAll('[data-modal]').forEach((modal) => {
    modal.addEventListener('mousedown', (event) => {
      if (event.target === modal) closeModal(modal)
    })
  })

  lockPageScroll()
  unlockPageScrollWhenIdle()

  const imagePreviewModal = document.querySelector('[data-image-preview-modal]')
  const imagePreviewImage = imagePreviewModal?.querySelector('[data-image-preview-image]')

  document.querySelectorAll('[data-image-preview-open]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!imagePreviewImage || !button.dataset.imagePreviewUrl) return

      imagePreviewImage.src = button.dataset.imagePreviewUrl
      imagePreviewImage.alt = button.dataset.imagePreviewAlt || 'Preview foto barang'
      openModal('image-preview')
    })
  })

  imagePreviewModal?.addEventListener('mousedown', (event) => {
    if (event.target === imagePreviewModal) closeModal(imagePreviewModal)
  })

  document.querySelectorAll('[data-report-document-period]').forEach((periodSelect) => {
    const form = periodSelect.closest('form')
    const yearSelect = form?.querySelector('[data-report-document-year]')
    if (!yearSelect) return

    let occupiedPeriodsByYear = {}
    try {
      occupiedPeriodsByYear = JSON.parse(periodSelect.dataset.occupiedPeriods || '{}')
    } catch (_) {
      occupiedPeriodsByYear = {}
    }

    const refreshAvailablePeriods = () => {
      const occupiedPeriods = new Set(occupiedPeriodsByYear[yearSelect.value] || [])

      Array.from(periodSelect.options).forEach((option) => {
        const occupied = occupiedPeriods.has(option.value)
        option.disabled = occupied
        option.textContent = `${option.dataset.periodLabel || option.textContent}${occupied ? ' — sudah memiliki dokumen' : ''}`
      })

      if (periodSelect.selectedOptions[0]?.disabled) {
        periodSelect.value = Array.from(periodSelect.options).find((option) => !option.disabled)?.value || ''
      }
    }

    yearSelect.addEventListener('change', refreshAvailablePeriods)
    refreshAvailablePeriods()
  })

  document.querySelectorAll('[data-berita-acara-holder]').forEach((holderSelect) => {
    const form = holderSelect.closest('form')
    const fields = {
      name: form?.querySelector('[data-berita-acara-field="name"]'),
      position: form?.querySelector('[data-berita-acara-field="position"]'),
      status: form?.querySelector('[data-berita-acara-field="status"]'),
      unit: form?.querySelector('[data-berita-acara-field="unit"]')
    }

    const populateHolderSnapshot = () => {
      const option = holderSelect.selectedOptions[0]
      Object.entries(fields).forEach(([field, input]) => {
        if (input) input.value = option?.dataset[field] || ''
      })
    }

    holderSelect.addEventListener('change', populateHolderSnapshot)
    populateHolderSnapshot()
  })

  const reconciliationPreviewModal = document.querySelector('[data-reconciliation-preview-modal]')
  const reconciliationPreviewFrame = reconciliationPreviewModal?.querySelector('[data-reconciliation-preview-frame]')
  const reconciliationPreviewLoading = reconciliationPreviewModal?.querySelector('[data-reconciliation-preview-loading]')
  const reconciliationPreviewError = reconciliationPreviewModal?.querySelector('[data-reconciliation-preview-error]')
  const reconciliationPreviewTitle = reconciliationPreviewModal?.querySelector('[data-reconciliation-preview-title]')
  const reconciliationPreviewFile = reconciliationPreviewModal?.querySelector('[data-reconciliation-preview-file]')
  const reconciliationPreviewDownload = reconciliationPreviewModal?.querySelector('[data-reconciliation-preview-download]')

  const resetReconciliationPreview = () => {
    if (!reconciliationPreviewFrame) return

    reconciliationPreviewFrame.removeAttribute('src')
    reconciliationPreviewFrame.classList.add('hidden')
    reconciliationPreviewLoading?.classList.remove('hidden')
    reconciliationPreviewError?.classList.add('hidden')
    if (reconciliationPreviewDownload) reconciliationPreviewDownload.href = '#'
  }

  document.querySelectorAll('[data-reconciliation-preview-open]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!reconciliationPreviewModal || !reconciliationPreviewFrame || !button.dataset.previewUrl) return

      resetReconciliationPreview()
      if (reconciliationPreviewTitle) reconciliationPreviewTitle.textContent = button.dataset.previewTitle || 'Preview PDF'
      if (reconciliationPreviewFile) reconciliationPreviewFile.textContent = button.dataset.previewFile || ''
      if (reconciliationPreviewDownload) reconciliationPreviewDownload.href = button.dataset.downloadUrl || '#'

      reconciliationPreviewFrame.src = button.dataset.previewUrl
      openModal('reconciliation-document-preview')
    })
  })

  reconciliationPreviewFrame?.addEventListener('load', () => {
    if (reconciliationPreviewModal?.classList.contains('hidden') || !reconciliationPreviewFrame.getAttribute('src')) return

    reconciliationPreviewLoading?.classList.add('hidden')
    reconciliationPreviewError?.classList.add('hidden')
    reconciliationPreviewFrame.classList.remove('hidden')
  })

  reconciliationPreviewFrame?.addEventListener('error', () => {
    reconciliationPreviewLoading?.classList.add('hidden')
    reconciliationPreviewError?.classList.remove('hidden')
    reconciliationPreviewFrame?.classList.add('hidden')
  })

  reconciliationPreviewModal?.querySelectorAll('[data-reconciliation-preview-close]').forEach((button) => {
    button.addEventListener('click', resetReconciliationPreview)
  })

  reconciliationPreviewModal?.addEventListener('mousedown', (event) => {
    if (event.target === reconciliationPreviewModal) {
      resetReconciliationPreview()
    }
  })

  window.setTimeout(() => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => element.remove())
  }, 3000)

  document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = document.getElementById(button.dataset.togglePassword)
      if (!target) return

      const isVisible = target.type === 'text'
      target.type = isVisible ? 'password' : 'text'
      button.querySelector('[data-password-icon-show]')?.classList.toggle('hidden', !isVisible)
      button.querySelector('[data-password-icon-hide]')?.classList.toggle('hidden', isVisible)
      button.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password')
    })
  })

  document.querySelectorAll('[data-submit-loading]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('[data-loading-button]')
      const label = button?.querySelector('[data-loading-label]')
      const spinner = button?.querySelector('[data-loading-spinner]')

      if (button) button.disabled = true
      if (label) label.textContent = button?.dataset.loadingText || 'Memproses...'
      if (spinner) spinner.classList.remove('hidden')
    })
  })

  document.querySelectorAll('[data-permission-form]').forEach((panel) => {
    const checkboxes = Array.from(panel.querySelectorAll('[data-permission-checkbox]'))
    const selectedCount = panel.querySelector('[data-permission-selected-count]')

    const refresh = () => {
      if (selectedCount) selectedCount.textContent = String(checkboxes.filter((checkbox) => checkbox.checked).length)
    }

    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener('change', refresh)
    })

    panel.querySelector('[data-permission-clear]')?.addEventListener('click', () => {
      checkboxes.forEach((checkbox) => { checkbox.checked = false })
      refresh()
    })

    refresh()
  })

  document.querySelectorAll('[data-condition-document-form]').forEach((form) => {
    const photoInput = form.querySelector('[data-condition-photo-input]')
    const uploadButton = form.querySelector('[data-condition-photo-upload]')
    const cameraButton = form.querySelector('[data-condition-photo-camera]')
    const cameraCaptureButton = form.querySelector('[data-condition-camera-capture]')
    const cameraCloseButton = form.querySelector('[data-condition-camera-close]')
    const cameraPanel = form.querySelector('[data-condition-camera-panel]')
    const cameraVideo = form.querySelector('[data-condition-camera-video]')
    const cameraCanvas = form.querySelector('[data-condition-camera-canvas]')
    const photoPreview = form.querySelector('[data-condition-photo-preview]')
    const photoCount = form.querySelector('[data-condition-photo-count]')
    const photoError = form.querySelector('[data-condition-photo-error]')
    const videoInput = form.querySelector('[data-condition-video-input]')
    const videoUploadButton = form.querySelector('[data-condition-video-upload]')
    const videoInfo = form.querySelector('[data-condition-video-info]')
    const videoRemoveButton = form.querySelector('[data-condition-video-remove]')
    const maxPhotos = 10
    const maxPhotoSize = 2 * 1024 * 1024
    const maxVideoSize = 20 * 1024 * 1024
    let selectedPhotoFiles = []
    let cameraStream = null

    if (!photoInput) return

    const showPhotoError = (message = '') => {
      if (!photoError) return
      photoError.textContent = message
      photoError.classList.toggle('hidden', !message)
    }

    const formatBytes = (bytes) => {
      if (!bytes) return '0 KB'
      if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
      return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
    }

    const getPhotoFiles = () => selectedPhotoFiles

    const setPhotoFiles = (files) => {
      selectedPhotoFiles = files
      const dataTransfer = new DataTransfer()
      files.forEach((file) => dataTransfer.items.add(file))
      photoInput.files = dataTransfer.files
      renderPhotoPreview()
    }

    const addPhotoFiles = (incomingFiles) => {
      showPhotoError('')
      const currentFiles = getPhotoFiles()
      const validFiles = []

      for (const file of incomingFiles) {
        if (!file.type.startsWith('image/')) {
          showPhotoError('File foto harus berupa gambar.')
          continue
        }

        if (file.size > maxPhotoSize) {
          showPhotoError('Ukuran setiap foto maksimal 2 MB.')
          continue
        }

        validFiles.push(file)
      }

      const mergedFiles = [...currentFiles, ...validFiles]

      if (mergedFiles.length > maxPhotos) {
        showPhotoError('Maksimal 10 foto dokumentasi.')
        setPhotoFiles(mergedFiles.slice(0, maxPhotos))
        return
      }

      setPhotoFiles(mergedFiles)
    }

    const renderPhotoPreview = () => {
      const files = getPhotoFiles()
      if (photoCount) photoCount.textContent = `Foto ${files.length}/10`
      if (!photoPreview) return

      photoPreview.innerHTML = ''

      files.forEach((file, index) => {
        const wrapper = document.createElement('div')
        wrapper.className = 'relative overflow-hidden rounded-[10px] border border-[#dce4ec] bg-white'

        const image = document.createElement('img')
        image.className = 'h-[72px] w-full object-cover'
        image.alt = `Foto dokumentasi ${index + 1}`
        image.src = URL.createObjectURL(file)
        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true })

        const removeButton = document.createElement('button')
        removeButton.type = 'button'
        removeButton.className = 'absolute right-1 top-1 grid h-5 w-5 place-items-center rounded-full border-0 bg-[rgb(11_43_80_/_82%)] text-[13px] font-bold leading-none text-white'
        removeButton.textContent = '×'
        removeButton.setAttribute('aria-label', `Hapus foto ${index + 1}`)
        removeButton.addEventListener('click', () => {
          const nextFiles = getPhotoFiles().filter((_, fileIndex) => fileIndex !== index)
          setPhotoFiles(nextFiles)
          showPhotoError('')
        })

        wrapper.append(image, removeButton)
        photoPreview.append(wrapper)
      })
    }

    const stopCamera = () => {
      cameraStream?.getTracks().forEach((track) => track.stop())
      cameraStream = null
      if (cameraVideo) cameraVideo.srcObject = null
      cameraPanel?.classList.add('hidden')
      cameraCaptureButton?.classList.add('hidden')
      cameraCaptureButton?.classList.remove('inline-flex')
      cameraCloseButton?.classList.add('hidden')
      cameraCloseButton?.classList.remove('inline-flex')
    }

    uploadButton?.addEventListener('click', () => photoInput.click())

    photoInput.addEventListener('change', () => {
      addPhotoFiles(Array.from(photoInput.files || []))
    })

    cameraButton?.addEventListener('click', () => {
      showPhotoError('')

      if (!navigator.mediaDevices?.getUserMedia) {
        showPhotoError('Browser ini belum mendukung akses kamera.')
        return
      }

      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
        .then((stream) => {
          stopCamera()
          cameraStream = stream
          if (cameraVideo) cameraVideo.srcObject = stream
          cameraPanel?.classList.remove('hidden')
          cameraCaptureButton?.classList.remove('hidden')
          cameraCaptureButton?.classList.add('inline-flex')
          cameraCloseButton?.classList.remove('hidden')
          cameraCloseButton?.classList.add('inline-flex')
        })
        .catch(() => {
          showPhotoError('Kamera tidak bisa dibuka. Pastikan izin kamera diizinkan browser.')
        })
    })

    cameraCaptureButton?.addEventListener('click', () => {
      if (!cameraVideo || !cameraCanvas) return

      const width = cameraVideo.videoWidth || 1280
      const height = cameraVideo.videoHeight || 720
      cameraCanvas.width = width
      cameraCanvas.height = height
      cameraCanvas.getContext('2d')?.drawImage(cameraVideo, 0, 0, width, height)

      cameraCanvas.toBlob((blob) => {
        if (!blob) {
          showPhotoError('Foto dari kamera gagal dibuat.')
          return
        }

        const file = new File([blob], `dokumentasi-kondisi-${Date.now()}.jpg`, { type: 'image/jpeg' })
        addPhotoFiles([file])
        stopCamera()
      }, 'image/jpeg', 0.85)
    })

    cameraCloseButton?.addEventListener('click', stopCamera)

    form.closest('[data-modal]')?.querySelectorAll('[data-modal-close]').forEach((button) => {
      button.addEventListener('click', stopCamera)
    })

    videoUploadButton?.addEventListener('click', () => videoInput?.click())

    videoInput?.addEventListener('change', () => {
      const file = videoInput.files?.[0]

      if (!file) {
        if (videoInfo) videoInfo.textContent = 'Belum ada video dipilih.'
        videoRemoveButton?.classList.add('hidden')
        videoRemoveButton?.classList.remove('inline-flex')
        return
      }

      if (file.size > maxVideoSize) {
        videoInput.value = ''
        if (videoInfo) videoInfo.textContent = 'Ukuran video maksimal 20 MB.'
        videoInfo?.classList.add('text-[#b42318]')
        videoRemoveButton?.classList.add('hidden')
        videoRemoveButton?.classList.remove('inline-flex')
        return
      }

      videoInfo?.classList.remove('text-[#b42318]')
      if (videoInfo) videoInfo.textContent = `${file.name} (${formatBytes(file.size)})`
      videoRemoveButton?.classList.remove('hidden')
      videoRemoveButton?.classList.add('inline-flex')
    })

    videoRemoveButton?.addEventListener('click', () => {
      if (videoInput) videoInput.value = ''
      if (videoInfo) videoInfo.textContent = 'Belum ada video dipilih.'
      videoInfo?.classList.remove('text-[#b42318]')
      videoRemoveButton.classList.add('hidden')
      videoRemoveButton.classList.remove('inline-flex')
    })

    renderPhotoPreview()
  })
})
