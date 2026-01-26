import { defineStore } from 'pinia'
export interface History {
  file: { path: string, name: string }
  count: number
  lastAccessDate: Date
  lastAccessUnixtime: number
}
/** 再生履歴 */
export interface Histories {
  /** ファイル名をそのままインデックスにする */
  [fileName: string]: History
}
export interface File {
  path: string
  name: string
  size: number
}

export const useStore = defineStore('store', {
  state: () => {
    return {
      /** ユーザーID */
      userId: '',
      /** パスワード */
      password: '',
      /** 接続先サーバー */
      server: '',
      /** ファイル一覧 */
      files: [] as File[],
      /** IPアドレス */
      ipAddress: '',
      /** ディスク総容量 */
      totalBytes: 0,
      /** ディスク空き容量 */
      freeBytes: 0,
      /** 再生履歴 */
      history: {} as Histories,
      /** 検索クエリ */
      query: '',
      selectedSortBy: '',
      selectedAscDesc: '',
    }
  },
  persist: true,
})
