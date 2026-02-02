<template lang="pug">
  .player-page(
    style="height: 100%; overflow: hidden;"
  )
    .video-wrap(
      ref="videoWrap"
      )
      video.video(
        autoplay
        loop
        playsinline
        preload="auto"
        ref="video"
        :class="isFullscreen ? 'video-fullscreen' : 'video-normal'"
        @click="showControls = !showControls"
        )
        source(
          :src="`${this.store.server}/${playing.path}`"
          type="video/mp4"
          )
      .controls(
        v-show="showControls"
        @click.stop="lastOpenPlayerControlsTime = Date.now()"
        )
        v-btn.play-pause(
          :icon="pause ? 'mdi-play' : 'mdi-pause'"
          size="large"
          style="background-color: rgba(0, 0, 0, 0.5); color: white;"
          @click="videoPlayPause()"
          )
        .under
          .flex(
            style="display: flex; align-items: center; padding: 0 16px;"
          )
            v-btn.under-button(
              icon="mdi-skip-backward"
              @click="videoBackward()"
              flat
              style=""
            )
            v-btn.under-button(
              icon="mdi-skip-forward"
              @click="videoForward()"
              flat
              style=""
            )
            v-spacer
            v-btn.under-button(
              :icon="isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen'"
              @click="videoFullscreen()"
              flat
              style=""
            )
          v-progress-linear.my-0(
            ref="progressBar"
            :model-value="progressParsent"
            height="32"
            color="rgba(var(--v-theme-primary), 0.6)"
            style="position: absolute; bottom: 0; left: 0; width: 100%; cursor: pointer; transform: translateY(32px);"
            @click="videoProgressClick($event)"
          )
          p(
            style="position: absolute; right: 16px; bottom: 12px; color: white; font-size: 14px; pointer-events: none;"
          ) {{ Math.floor(currentTime / 60) }}:{{ ('0' + Math.floor(currentTime % 60)).slice(-2) }} / {{ Math.floor(duration / 60) }}:{{ ('0' + Math.floor(duration % 60)).slice(-2) }}
    v-card(
      style="overflow-y: auto; height: -webkit-fill-available; height: -moz-fill-available;"
    )
      v-card-title(
        style="font-size: 24px; font-weight: bold;"
        ) {{ playing.name }}
      v-card-text
        .actions(
          style="display: flex; align-items: center; margin-bottom: 16px;"
        )
          v-card-subtitle 再生回数: {{ playCount }} 回
          v-spacer
          v-btn(
            icon="mdi-dots-vertical"
            @click="infoDialog = true; infoFile = playing"
          )
          v-btn(
            icon="mdi-download"
            @click="download(playing.path)"
          )
          v-btn(
            icon="mdi-share-variant"
            @click="share(currentPath)"
            )
        table.play-info-table
          tr
            th
              v-icon mdi-subtitles-outline
            td {{ playing.name }}
          //- tr
            th
              v-icon mdi-link-variant
            td {{ currentPath }}
          tr
            th
              v-icon mdi-file-outline
            td {{ (playing.size / 1024 / 1024).toFixed(2) }} MB
        .feature
          p.feature-title.text-h6.mt-4 オススメ（{{ store.searchResults.length }}個の候補）
          v-list(lines="three")
            v-list-item.list-item(
              v-for="(file, cnt) in store.searchResults"
              :key="file.path"
              @click="$router.push({ path: '/player', query: { file: file.path } })"
              link
              )
              v-list-item-content
                v-list-item-title {{ file.name }}
                v-list-item-subtitle {{ (file.size / 1024 / 1024).toFixed(2) }} MB
              template(v-slot:append)
                v-btn(
                  icon
                  @click.stop="infoDialog = true; infoFile = file"
                  )
                  v-icon mdi-dots-vertical
              template(v-slot:prepend)
                v-list-item-action
                  v-btn(
                    icon="mdi-video-outline"
                    )
  v-dialog(
    v-model="infoDialog"
    max-width="500"
  )
    v-card
      v-card-title ファイル情報
      v-card-text
        v-list
          v-list-item
            v-list-item-content
              v-list-item-title ファイル名
              v-list-item-subtitle(
                style="white-space: normal; -webkit-line-clamp: unset; -moz-line-clamp: unset;"
              ) {{ infoFile.name }}
              v-list-item-action
                v-btn.my-2(
                  prepend-icon="mdi-content-copy"
                  style="background-color: rgb(var(--v-theme-primary)); color: white;"
                  @click="copy(infoFile.name)"
                  ) タイトルをコピー
          v-list-item
            v-list-item-content
              v-list-item-title ファイルパス
              v-list-item-subtitle(
                style="white-space: normal; -webkit-line-clamp: unset; -moz-line-clamp: unset;"
              ) {{ store.server }}{{ infoFile.path }}
          v-list-item
            v-list-item-content
              v-list-item-title ファイルサイズ
              v-list-item-subtitle {{ (infoFile.size / 1024 / 1024).toFixed(2) }} MB
      v-card-actions
        v-spacer
        v-btn(
          @click="infoDialog = false"
          style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ) 閉じる
</template>
<script lang="ts">
  import { App } from '@capacitor/app'
  import { Share } from '@capacitor/share'
  // @ts-ignore
  import { useStore } from '@/stores/store'

  export default {
    data () {
      return {
        store: useStore(),
        playing: {
          path: '',
          name: '',
          size: 0,
        },
        /** 再生回数 */
        playCount: 0,
        /** 現在のパス */
        currentPath: '',
        /** 動画の一時停止状態 */
        pause: false,
        /** 動画コントロール表示状態 */
        showControls: true,
        /** フルスクリーン状態 */
        isFullscreen: false,
        /** 動画の進捗パーセント */
        progressParsent: 0,
        /** 動画の総再生時間 */
        duration: 0,
        /** 動画の現在の再生時間 */
        currentTime: 0,
        /** 最後に動画コントロールを開いた時間 */
        lastOpenPlayerControlsTime: 0,
        /** ファイル情報ダイアログ表示状態 */
        infoDialog: false,
      }
    },
    watch: {
      showControls: {
        immediate: true,
        handler (newVal: boolean) {
          if (newVal) {
            this.lastOpenPlayerControlsTime = Date.now()
            setTimeout(() => {
              // 3秒経過してから閉じる
              if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
                this.showControls = false
              }
            }, 3000)
          }
        },
      },
    },
    mounted () {
      const url = new URL(window.location.href)
      this.currentPath = `${this.store.server}${url.searchParams.get('file')}`

      const filePath = url.searchParams.get('file')
      const playingFile = this.store.files.find(file => file.path === filePath)
      if (playingFile) {
        this.playing = playingFile
      }

      setTimeout(() => {
        if (this.$refs.video == null) {
          return
        }
        const videoElement = this.$refs.video as HTMLVideoElement
        this.pause = videoElement.paused ? true : false

        videoElement.addEventListener('mouseleave', () => {
          setTimeout(() => {
            if (videoElement.matches(':hover')) {
              return
            }
            // 3秒経過してから閉じる
            if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
              this.showControls = false
            }
          }, 3000)
        })

        videoElement.addEventListener('mouseenter', () => {
          this.showControls = true
        })

        // 読み込み完了時に再生状況を入れる
        videoElement.addEventListener('loadeddata', () => {
          setTimeout(() => {
            this.pause = videoElement.paused ? true : false
          }, 100)
        })

        videoElement.addEventListener('timeupdate', () => {
          this.currentTime = videoElement.currentTime
          this.duration = videoElement.duration
          this.progressParsent = (this.currentTime / this.duration) * 100
        })

        // フルスクリーン状態の監視
        const videoWrapElement = this.$refs.videoWrap as HTMLDivElement
        videoWrapElement.addEventListener('fullscreenchange', () => {
          this.isFullscreen = document.fullscreenElement ? true : false
        })

        // スペースキーで再生・一時停止切り替え
        document.addEventListener('keydown', this.keyInput)
      }, 100)

      App.addListener('backButton', () => {
        // ここにバックボタンが押されたときの処理を記述
        if (this.$refs.video) {
          if (this.isFullscreen) {
            this.videoFullscreen()
          } else {
            this.$router.back()
          }
        } else {
          this.$router.back()
        }
      })
    },
    unmounted () {
      document.removeEventListener('keydown', this.keyInput)
      App.removeAllListeners()
    },
    methods: {
      /** 共有 */
      share (text: string) {
        navigator.clipboard.writeText(text)
        Share.share({
          title: '共有リンク',
          url: text,
        })
      },
      /** クリップボードにコピー */
      copy (text: string) {
        navigator.clipboard.writeText(text)
      },
      /** ファイルをダウンロード */
      download (filePath: string) {
        const link = document.createElement('a')
        link.href = `${this.store.server}/${filePath}`
        link.download = ''
        link.target = '_blank'
        document.body.append(link)
        link.click()
        link.remove()
      },
      /** 動画の再生・一時停止切り替え */
      videoPlayPause () {
        const videoElement = this.$refs.video as HTMLVideoElement
        if (videoElement.paused) {
          this.pause = false
          videoElement.play()
        } else {
          this.pause = true
          videoElement.pause()
        }
        setTimeout(() => {
          // 3秒経過してから閉じる
          if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
            this.showControls = false
          }
        }, 3000)
      },
      /** 動画をフルスクリーン表示 */
      videoFullscreen () {
        const videoWrapElement = this.$refs.videoWrap as HTMLDivElement
        if (this.isFullscreen) {
          if (document.exitFullscreen) {
            document.exitFullscreen()
          } else if ((document as any).mozCancelFullScreen) { /* Firefox */
            (document as any).mozCancelFullScreen()
          } else if ((document as any).webkitExitFullscreen) { /* Chrome, Safari and Opera */
            (document as any).webkitExitFullscreen()
          } else if ((document as any).msExitFullscreen) { /* IE/Edge */
            (document as any).msExitFullscreen()
          }
        } else {
          if (videoWrapElement.requestFullscreen) {
            videoWrapElement.requestFullscreen()
          } else if ((videoWrapElement as any).mozRequestFullScreen) { /* Firefox */
            (videoWrapElement as any).mozRequestFullScreen()
          } else if ((videoWrapElement as any).webkitRequestFullscreen) { /* Chrome, Safari & Opera */
            (videoWrapElement as any).webkitRequestFullscreen()
          } else if ((videoWrapElement as any).msRequestFullscreen) { /* IE/Edge */
            (videoWrapElement as any).msRequestFullscreen()
          }
        }
        setTimeout(() => {
          // 3秒経過してから閉じる
          if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
            this.showControls = false
          }
        }, 3000)
      },
      /** 10秒戻る */
      videoBackward () {
        const videoElement = this.$refs.video as HTMLVideoElement
        videoElement.currentTime = Math.max(videoElement.currentTime - 10, 0)
        setTimeout(() => {
          // 3秒経過してから閉じる
          if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
            this.showControls = false
          }
        }, 3000)
      },
      /** 10秒進む */
      videoForward () {
        const videoElement = this.$refs.video as HTMLVideoElement
        videoElement.currentTime = Math.min(videoElement.currentTime + 10, videoElement.duration)
        setTimeout(() => {
          // 3秒経過してから閉じる
          if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
            this.showControls = false
          }
        }, 3000)
      },
      /** 動画の進捗バーをクリックしたときの処理 */
      videoProgressClick (event: MouseEvent) {
        const ref = this.$refs.progressBar

        // event は MouseEvent | TouchEvent | PointerEvent と想定
        let clientX: number

        // eslint-disable-next-line unicorn/prefer-ternary
        if ('touches' in event) {
          // TouchEventの場合
          // @ts-ignore
          clientX = event.touches[0].clientX
        } else {
          // MouseEventの場合
          clientX = (event as MouseEvent).clientX
        }

        // @ts-ignore
        const progressBarElement = (ref.$el || ref) as HTMLElement
        const rect = progressBarElement.getBoundingClientRect()

        // 2. 割合を計算し、0.0〜1.0 の範囲に制限（クランプ）
        let clickPercent = (clientX - rect.left) / rect.width
        clickPercent = Math.max(0, Math.min(1, clickPercent))

        const videoElement = this.$refs.video as HTMLVideoElement
        if (!videoElement.duration) {
          return
        }
        videoElement.currentTime = videoElement.duration * clickPercent
        if (!videoElement.paused) {
          videoElement.pause()
          videoElement.play()
        }
        setTimeout(() => {
          // 3秒経過してから閉じる
          if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
            this.showControls = false
          }
        }, 3000)
      },
      keyInput (e: KeyboardEvent) {
        switch (e.code) {
          case 'Space': {
            this.videoPlayPause()
            e.preventDefault()

            break
          }
          case 'ArrowLeft': {
            this.videoBackward()
            e.preventDefault()

            break
          }
          case 'ArrowRight': {
            this.videoForward()
            e.preventDefault()

            break
          }
          // No default
        }
        this.showControls = true
        this.lastOpenPlayerControlsTime = Date.now()
        setTimeout(() => {
          // 3秒経過してから閉じる
          if (Date.now() - this.lastOpenPlayerControlsTime >= 3000) {
            this.showControls = false
          }
        }, 3000)
      },
      changePlaying (file: any) {
        this.$router.push({ path: '/player', query: { file: file.path } })
        // @ts-ignore
        this.$refs.video.src = `${this.store.server}${file.path}`
        this.currentPath = `${this.store.server}${file.path}`
        this.playing = file
      },
    },
  }
</script>

<style lang="scss" scoped>
.play-info-table {
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  border-collapse: collapse;
  display: table;
  table-layout: fixed;
  th, td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    overflow-wrap: break-word !important;
    word-wrap: break-word !important;
    white-space: normal;
  }
  th {
    width: 40px;
    vertical-align: top;
  }
}

.video-normal {
  width: 100%;
  height: calc(100vw * 9 / 18);
  background-color: black;
  object-fit: cover;
}

.video-fullscreen {
  width: 100vw;
  height: 100vh;
}

.video-wrap {
  position: relative;
  .controls {
    .play-pause {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      margin: auto;
    }
    .under {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 60px;
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
      .under-button{
        background-color: transparent;
        bottom: 16px;
        color: white;
        &:hover {
          background-color: rgba(0, 0, 0, 0.2);
        }
      }
    }
  }
}
</style>
