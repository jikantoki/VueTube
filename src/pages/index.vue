<template lang="pug">
  v-card
    v-card-title.my-2(
      style="display: flex; align-items: center;"
      )
      p ファイル（{{ searchResults.length }}個）
      v-spacer
      v-btn(
        prepend-icon="mdi-shuffle"
        append-icon="mdi-play"
        @click="randomPlay"
        style="background-color: rgb(var(--v-theme-primary)); color: white;"
        :disabled="searchResults.length === 0"
      ) ランダム再生
    v-card-text
      v-text-field(
        label="検索"
        v-model="query"
        append-icon="mdi-magnify"
        clearable
        )
      .playbuttons(
        style="display: flex; margin-bottom: 10px;"
      )
        v-select(
          v-model="selectedSortBy"
          :items="sortByList"
          label="並び替え"
          style="max-width: 200px; margin-right: 10px;"
        )
        v-select(
          v-model="selectedAscDesc"
          :items="ascDescList"
          label="順序"
          style="max-width: 200px;"
        )
      h2.ma-16(
        style="text-align: center;"
        v-if="searchResults.length === 0"
      ) 検索結果がありません
      v-list(lines="three")
        v-list-item.list-item(
          v-for="(file, cnt) in searchResults"
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
                style="white-space: normal; -webkit-line-clamp: unset;"
              ) {{ infoFile.name }}
              v-list-item-action
                v-btn.my-2(
                  prepend-icon="mdi-content-copy"
                  style="background-color: rgb(var(--v-theme-primary)); color: white;"
                  @click="copy(infoFile.name)"
                  ) ファイル名をコピー
          v-list-item
            v-list-item-content
              v-list-item-title ファイルパス
              v-list-item-subtitle(
                style="white-space: normal; -webkit-line-clamp: unset;"
              ) {{ infoFile.path }}
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
  v-dialog(
    v-model="errorDialog"
    max-width="500"
  )
    v-card
      v-card-title 認証エラー
      v-card-text {{ errorMessage }}
      v-card-actions
        v-spacer
        v-btn(
          @click="$router.push('/login')"
        ) 再ログイン
        v-btn(
          @click="errorDialog = false"
          style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ) 閉じる
</template>

<script lang="ts">
  import { useStore } from '@/stores/store'

  interface File {
    path: string
    name: string
    size: number
  }
  interface ResponseData {
    files: Array<File>
    ip: string
    totalBytes: number
    freeBytes: number
  }

  export default {
    data () {
      return {
        store: useStore(),
        errorDialog: false,
        errorMessage: '',
        query: '',
        searchResults: [] as File[],
        infoDialog: false,
        infoFile: {} as File,
        sortByList: [
          '名前',
          'サイズ',
          // '再生回数',
          'ランダム',
        ],
        selectedSortBy: '名前',
        ascDescList: [
          '↑',
          '↓',
        ],
        selectedAscDesc: '↑',
      }
    },
    watch: {
      query: {
        handler (newQuery: string) {
          localStorage.setItem('query', newQuery)
          if (!newQuery || newQuery.length === 0) {
            this.searchResults = this.store.files
            return
          }
          const lowerQuery = newQuery.toLowerCase()
          const splitedQuery = lowerQuery.split(' ')
          this.searchResults = this.store.files.filter((file: File) => {
            const lowerName = file.name.toLowerCase()
            return splitedQuery.every((q: string) => lowerName.includes(q))
          })
        },
      },
      selectedSortBy: {
        handler (newSortBy: string) {
          localStorage.setItem('selectedSortBy', newSortBy)
          this.sortResults()
        },
      },
      selectedAscDesc: {
        handler (newAscDesc: string) {
          localStorage.setItem('selectedAscDesc', newAscDesc)
          this.sortResults()
        },
      },
    },
    async mounted () {
      // localStorageから情報を取得する
      const savedQuery = localStorage.getItem('query')
      if (savedQuery && savedQuery.length > 0 && savedQuery !== 'null') {
        this.query = savedQuery
      }
      const savedSortBy = localStorage.getItem('selectedSortBy')
      if (savedSortBy) {
        this.selectedSortBy = savedSortBy
      }
      const savedAscDesc = localStorage.getItem('selectedAscDesc')
      if (savedAscDesc) {
        this.selectedAscDesc = savedAscDesc
      }

      // 保存されているログイン情報を取得
      const userId = localStorage.getItem('userId')
      const password = localStorage.getItem('password')

      if (!userId || !password) {
        this.$router.push('/login')
        return
      }
      if (userId) {
        this.store.userId = userId
      }
      if (password) {
        this.store.password = password
      }

      // 保存されているファイル情報を取得
      const files = localStorage.getItem('files')
      if (files) {
        this.store.files = JSON.parse(files)
        this.searchResults = this.store.files
      }

      try {
        const response = await fetch(`${this.store.server}/main.php`, {
          method: 'POST',
          headers: {
            id: this.store.userId,
            password: this.store.password,
          },
        })
        if (response.status !== 200) {
          throw new Error(`HTTPステータス${response.status}: ログインに失敗しました。IDまたはパスワードを確認してください。サーバーが圏外の可能性もあります`)
        }
        const data: ResponseData = await response.json()

        // ログイン成功したので情報を保存
        localStorage.setItem('userId', this.store.userId)
        localStorage.setItem('password', this.store.password)
        if (data.files) {
          this.store.files = data.files
          localStorage.setItem('files', JSON.stringify(this.store.files))
          // this.searchResults = this.store.files
        }
        if (data.ip) {
          this.store.ipAddress = data.ip
        }
        if (data.totalBytes) {
          this.store.totalBytes = data.totalBytes
        }
        if (data.freeBytes) {
          this.store.freeBytes = data.freeBytes
        }
      } catch (error) {
        this.errorDialog = true
        this.errorMessage = (error as Error).message
        console.error(error)
      }
    },
    methods: {
      copy (text: string) {
        navigator.clipboard.writeText(text)
      },
      randomPlay () {
        if (this.searchResults.length === 0) {
          return
        }
        const randomIndex = Math.floor(Math.random() * this.searchResults.length)
        const file = this.searchResults[randomIndex]
        this.$router.push({ path: '/player', query: { file: file?.path } })
      },
      sortResults () {
        const asc = this.selectedAscDesc === '↑'
        this.searchResults.sort((a: File, b: File) => {
          let compare = 0
          switch (this.selectedSortBy) {
            case '名前': {
              compare = a.name.localeCompare(b.name)
              break
            }
            case 'サイズ': {
              compare = a.size - b.size
              break
            }
            case '再生回数': {
              // compare = (this.store.count[a.path] || 0) - (this.store.playCounts[b.path] || 0)
              break
            }
            case 'ランダム': {
              compare = Math.random() - 0.5
              break
            }
          }
          return asc ? compare : -compare
        })
      } },
  }
</script>

<style lang="scss" scoped>
  .list-item {
    transition: background-color 0.2s;
    &:hover {
      background-color: rgba(var(--v-theme-primary), 0.2);
      cursor: pointer;
    }
  }
</style>
