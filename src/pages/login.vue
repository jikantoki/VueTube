<template lang="pug">
.login-page(
  style="display: flex; justify-content: center; align-items: center; height: 100vh;"
)
  v-card.pa-8(
    width="90%"
    style="max-width: 600px; border-radius: 16px;"
  )
    v-card-title ログイン
    v-card-text
      v-text-field(
        type="text"
        v-model="userId"
        placeholder="XXXXXX"
        label="ユーザーID"
        autocomplete="username"
        clearable
        ref="userId"
        @keyup.enter="$refs.password.focus()"
        )
      v-text-field(
        type="password"
        v-model="password"
        placeholder="Password"
        label="パスワード"
        autocomplete="current-password"
        clearable
        ref="password"
        @keyup.enter="$refs.server.focus()"
        )
      v-text-field(
        type="text"
        v-model="server"
        placeholder="https://サーバー.com/"
        label="接続先サーバー"
        autocomplete="organization"
        clearable
        ref="server"
        @keyup.enter="loginAuth()"
        )
      p(
        style="height: 3em; color: red;"
      ) {{ errorMessage }}
    v-card-actions
      v-spacer
      v-btn(
        @click="loginAuth"
        style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ref="loginButton"
      ) ログイン
</template>

<script lang="ts">
  import { useStore } from '@/stores/store'

  export default {
    data () {
      return {
        userId: '',
        password: '',
        server: '',
        errorMessage: '',
        store: useStore(),
      }
    },
    mounted () {
      const userId = localStorage.getItem('userId')
      const password = localStorage.getItem('password')
      const server = localStorage.getItem('server')
      if (server) {
        this.store.server = server
        this.server = server
      }
      if (userId) {
        this.store.userId = userId
        this.userId = userId
      }
      if (password) {
        this.store.password = password
        this.password = password
      }
    },
    methods: {
      async loginAuth () {
        try {
          const response = await fetch(`${this.server}/main.php`, {
            method: 'POST',
            headers: {
              id: this.userId,
              password: this.password,
            },
          })
          if (response.status !== 200) {
            throw new Error(`HTTPステータス${response.status}: ログインに失敗しました。IDまたはパスワードを確認してください。サーバーが圏外の可能性もあります`)
          }

          // ログイン成功したので情報を保存
          localStorage.setItem('userId', this.userId)
          localStorage.setItem('password', this.password)
          localStorage.setItem('server', this.server)
          this.store.userId = this.userId
          this.store.password = this.password
          this.store.server = this.server
          this.$router.push('/')
          return true
        } catch (error) {
          console.log(error)
          this.errorMessage = (error as Error).message
          return false
        }
      },
    },
  }
</script>

<style lang="scss">

</style>
