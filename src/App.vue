<template lang="pug">
  v-app
    header
      v-app-bar.pt-8(
        elevate-on-scroll
        )
        //- v-app-bar-nav-icon(
          @click="mainDrawer = !mainDrawer"
          )
        .nav-button.ma-2
          button.icon(
            v-ripple
            @click="mainDrawer = !mainDrawer"
            style="border-radius: 999px; width: 3em; height: 3em;"
          )
            img(
              src="/icon.png"
              alt="VueTube Logo"
              style="width: 2em; height: 2em;"
              )
        v-toolbar-title.ml-2 VueTube
        v-spacer
        .menu.mr-2
          v-menu(
            activator="parent"
            )
            template(v-slot:activator="{ props }")
              v-btn(
                v-bind="props"
                icon
                )
                v-icon mdi-dots-vertical
            v-list
              v-list-item(
                @click="aboutDialog = true"
                )
                v-list-item-icon
                  v-icon mdi-information
                v-list-item-title About
      v-navigation-drawer(
        v-model="mainDrawer"
        app
        temporary
        )
        v-list.mt-8(
          nav
          dense
          )
          v-list-item-group
            v-list-item(
              v-if="!store.userId || store.userId.length === 0"
              @click="$router.push('/login')"
              to="/login"
              link
              )
              v-list-item-icon
                v-icon mdi-login
              v-list-item-title ログイン
            v-list-item(
              v-if="store.userId && store.userId.length"
              to="/"
              link
              )
              v-list-item-icon
                v-icon mdi-home
              v-list-item-title トップ
            v-list-item(
              @click="aboutDialog = true"
              )
              v-list-item-icon
                v-icon mdi-information
              v-list-item-title About
            v-list-item(
              @click="logout"
              v-if="store.userId && store.userId.length"
              )
              v-list-item-icon
                v-icon mdi-logout
              v-list-item-title ログアウト
    v-main.mt-8.mb-4
      router-view
    v-footer.pb-4(
      v-if="store.userId && store.userId.length"
      app
      padless
      )
      .icon
        v-btn(
          icon="mdi-home"
          @click="jumpTop"
          )
    v-dialog(
      v-model="aboutDialog"
      max-width="500"
    )
      v-card
        v-card-title 情報
        v-card-text
          img.mb-8(
            src="/icon.png"
            alt="VueTube Logo"
            style="width: 128px; height: auto; margin-bottom: 16px;margin: auto; display: block;"
            )
          p VueTube バージョン: 1.0.0
          p ユーザー名: {{ store.userId }}
          p IPアドレス: {{ store.ipAddress }}
          p 接続先サーバー: {{ store.server }}
          hr.my-4
          p サーバー使用量: {{ stringUsedBytes }} / {{ stringTotalBytes }} ({{ Math.floor(storageParsent) }}%)
          v-progress-linear.my-4(
            :model-value="storageParsent"
            height="20"
            color="blue"
            )
          hr.my-4
          p 製作者情報:
          p &copy; 2021 エノキ電機
        v-card-actions
          v-spacer
          v-btn(
            @click="aboutDialog = false"
            style="background-color: rgb(var(--v-theme-primary)); color: white;"
          ) 閉じる
</template>

<script lang="ts">
  import { App } from '@capacitor/app'
  import { useStore } from './stores/store'
  export default {
    name: 'App',
    data () {
      return {
        store: useStore(),
        mainDrawer: false,
        aboutDialog: false,
      }
    },
    computed: {
      /** ディスク総容量（GB） */
      stringTotalBytes (): string {
        return (this.store.totalBytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB'
      },
      /** ディスク空き容量（GB） */
      stringFreeBytes (): string {
        return (this.store.freeBytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB'
      },
      /** ディスク使用量 */
      usedBytes (): number {
        return this.store.totalBytes - this.store.freeBytes
      },
      /** ディスク使用量（GB） */
      stringUsedBytes (): string {
        return (this.usedBytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB'
      },
      storageParsent (): number {
        if (this.store.totalBytes === 0) {
          return 0
        }
        return (this.usedBytes / this.store.totalBytes) * 100
      },
    },
    mounted () {
      document.title = 'VueTube'
      App.addListener('backButton', () => {
        // ここにバックボタンが押されたときの処理を記述
        if (this.$router.currentRoute.value.path === '/') {
          App.minimizeApp()
        } else {
          this.$router.back()
        }
      })
    },
    methods: {
      logout () {
        localStorage.removeItem('userId')
        localStorage.removeItem('password')
        this.store.userId = ''
        this.store.password = ''
        this.$router.push('/login')
      },
      jumpTop () {
        this.$router.push('/')
        window.scroll({ top: 0, behavior: 'smooth' })
      },
    },
  }
</script>

<style lang="scss">
.v-list-item__content {
  display: flex;
  align-items: center;
  gap: 8px;
}

* {
  user-select: none;
}
</style>
