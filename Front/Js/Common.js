/* ==========================================================================
 * 定数定義
 * ========================================================================== */
// 1. APIベースURL
const API_BASE_URL = '/Back/Api'
// 2. 1ページあたりの表示件数
const RECORDS_PER_PAGE = 15
// 3. セッションタイムアウト時間
const SESSION_TIMEOUT = 60 * 60 * 1000

/**
 * UUIDの生成を行う関数
 * @returns {string} 生成されたUUIDの文字列
 */
function GenerateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    const r = (Math.random() * 16) | 0
    const v = c === 'x' ? r : (r & 0x3) | 0x8
    return v.toString(16)
  })
}

/* ==========================================================================
 * 文字列操作
 * ========================================================================== */
/**
 * 文字数を制限して末尾を省略する関数
 * @param {string} Text 末尾の省略を行う文字列
 * @param {number} MaxLength 省略を行う基準となる桁数の指定
 * @returns {string} 省略後の文字列を返す
 */
function TruncateText(Text, MaxLength = 20) {
  if (!Text) {
    return ''
  }
  if (Text.length > MaxLength) {
    return Text.slice(0, MaxLength) + '…'
  }
  return Text
}

/**
 * 数値を3桁カンマ区切りにフォーマットする関数
 * @param {number|string} Num フォーマット対象の数値
 * @returns {string} カンマ区切りされた文字列
 */
function FormatNumber(Num) {
  if (Num === null || Num === undefined || Num === '') {
    return ''
  }
  return Number(Num).toLocaleString()
}

/**
 * 日付をyyyy年MM月dd日形式にフォーマットする関数
 * @param {string} DateStr 日付文字列（YYYY-MM-DD形式など）
 * @returns {string} フォーマットされた日付文字列
 */
function FormatDate(DateStr) {
  if (!DateStr) {
    return ''
  }
  const Cleaned = DateStr.replace(/[^0-9]/g, '')
  if (Cleaned.length === 8) {
    const Year = Cleaned.slice(0, 4)
    const Month = Cleaned.slice(4, 6)
    const Day = Cleaned.slice(6, 8)
    return `${Year}年${Month}月${Day}日`
  } else if (Cleaned.length === 6) {
    const Year = Cleaned.slice(0, 4)
    const Month = Cleaned.slice(4, 6)
    return `${Year}年${Month}月`
  } else if (Cleaned.length === 4) {
    return `${Cleaned}年`
  }
  return DateStr
}

/**
 * タイムスタンプをyyyy年MM月dd日形式にフォーマットする関数
 * @param {string} Timestamp タイムスタンプ文字列
 * @returns {string} フォーマットされた日付文字列
 */
function FormatTimestamp(Timestamp) {
  if (!Timestamp) {
    return ''
  }
  const DateInstance = new Date(Timestamp)
  if (isNaN(DateInstance.getTime())) {
    return ''
  }
  const Year = DateInstance.getFullYear()
  const Month = String(DateInstance.getMonth() + 1).padStart(2, '0')
  const Day = String(DateInstance.getDate()).padStart(2, '0')
  return `${Year}年${Month}月${Day}日`
}
/**
 * 日時を「yyyy年M月d日 (曜日)H時m分s.ミリ秒秒」形式にフォーマットする関数
 * @param {string} dateTimeStr 日時文字列（例: "2026-01-07 16:24:23.058"）
 * @returns {string} フォーマットされた日時文字列
 */
function FormatDateTimeWithWeekday(dateTimeStr) {
  if (!dateTimeStr) return ''

  // "2026-01-07 16:24:23.058" → "2026-01-07T16:24:23.058"
  const isoStr = dateTimeStr.replace(' ', 'T')
  const date = new Date(isoStr)

  if (isNaN(date.getTime())) return dateTimeStr

  const weekdays = ['日', '月', '火', '水', '木', '金', '土']
  const year = date.getFullYear()
  const month = date.getMonth() + 1
  const day = date.getDate()
  const weekday = weekdays[date.getDay()]
  const hour = date.getHours()
  const minute = date.getMinutes()
  const second = date.getSeconds()
  const ms = date.getMilliseconds()

  return `${year}年${month}月${day}日 (${weekday})${hour}時${minute}分${second}.${ms}秒`
}

/**
 * 日時をフォーマットして表示用に変換
 * @param {string} DateStr 日時文字列
 * @returns {string} フォーマット済み日時
 */
function FormatDisplayDateTime(DateStr) {
  if (!DateStr) return ''
  const D = new Date(DateStr)
  if (isNaN(D.getTime())) return DateStr
  const Y = D.getFullYear()
  const M = String(D.getMonth() + 1).padStart(2, '0')
  const Day = String(D.getDate()).padStart(2, '0')
  const H = String(D.getHours()).padStart(2, '0')
  const Min = String(D.getMinutes()).padStart(2, '0')
  return `${Y}年${M}月${Day}日 ${H}:${Min}`
}

/* ==========================================================================
 * プルダウン関連
 * ========================================================================== */
/**
 * プルダウンの内容を生成する処理
 * @param {string} Type 取得するプルダウンのキー
 * @returns {string} optionタグ群の文字列
 */
function PullDownList(Type) {
  const DataMap = {
    /** 作品ジャンル区分 */
    Genre: [
      { value: 0, label: '未設定' },
      { value: 1, label: 'ホラー' },
      { value: 2, label: 'ファンタジー' },
      { value: 3, label: 'SF' },
      { value: 4, label: '恋愛' },
      { value: 5, label: '日常' },
      { value: 6, label: 'アクション' },
      { value: 7, label: '冒険' },
      { value: 8, label: 'ミステリー' },
      { value: 9, label: 'コメディ' },
      { value: 10, label: 'スポーツ' },
      { value: 11, label: '歴史' },
      { value: 12, label: '学園' },
      { value: 13, label: 'その他' },
    ],
    /** レビュー評価区分 */
    Review: [
      { value: 0, label: '' },
      { value: 1, label: '◎' },
      { value: 2, label: '〇' },
      { value: 3, label: '△' },
      { value: 4, label: '✕' },
    ],
    /** 性別区分 */
    Gender: [
      { value: 0, label: '未設定' },
      { value: 1, label: '男性' },
      { value: 2, label: '女性' },
      { value: 3, label: 'その他' },
    ],
    /** 起承転結区分 */
    PlotStructure: [
      { value: 0, label: '起' },
      { value: 1, label: '承' },
      { value: 2, label: '転' },
      { value: 3, label: '結' },
    ],
  }

  const List = DataMap[Type]
  if (!List) {
    return ''
  }

  const ZeroIndex = List.findIndex((Item) => Item.value === 0)
  let Items
  if (ZeroIndex !== -1) {
    if (ZeroIndex === 0) {
      Items = List.slice()
    } else {
      Items = [List[ZeroIndex]].concat(List.filter((_, i) => i !== ZeroIndex))
    }
  } else {
    Items = List.slice()
  }

  return Items.map((Item, Idx) => {
    if (Idx === 0) {
      return `<option value="${Item.value}" selected data-placeholder="true">${Item.label}</option>`
    }
    return `<option value="${Item.value}">${Item.label}</option>`
  }).join('')
}

/**
 * CDをもとにプルダウンの値を取得する関数
 * @param {string} Type PullDownList関数に指定するキー
 * @param {string|number} Cd 値を取得したいCD
 * @returns {string} 取得したCDの値
 */
function GetKeyInfo(Type, Cd) {
  const OptionsHtml = PullDownList(Type)
  const Temp = document.createElement('select')
  Temp.innerHTML = OptionsHtml
  const Option = Array.from(Temp.options).find((Opt) => Opt.value == Cd)
  return Option ? Option.text : ''
}

/* ==========================================================================
 * API呼び出し
 * ========================================================================== */
/**
 * API呼び出しを行う共通関数
 * @param {string} Endpoint APIエンドポイント
 * @param {string} Method HTTPメソッド
 * @param {Object} Data 送信データ
 * @returns {Promise<Object>} APIレスポンス
 */
async function CallApi(Endpoint, Method = 'GET', Data = null) {
  ShowSpinner()
  try {
    const Options = {
      method: Method,
      headers: {
        'Content-Type': 'application/json',
      },
    }
    if (Data && Method !== 'GET') {
      Options.body = JSON.stringify(Data)
    }
    const Response = await fetch(`${API_BASE_URL}${Endpoint}`, Options)
    const Result = await Response.json()

    if (!Response.ok) {
      throw new Error(Result.Message || 'APIエラーが発生しました')
    }
    return Result
  } catch (Error) {
    console.error('API Error:', Error)
    throw Error
  } finally {
    HideSpinner()
  }
}

/**
 * ファイルをBase64文字列に変換する共通関数
 * @param {File} file 
 * @returns {Promise<string>} Base64文字列
 */
function ConvertFileToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result); // データURL（data:image/png;base64,...）を返す
    reader.onerror = error => reject(error);
  });
}

/* ==========================================================================
 * スピナー制御
 * ========================================================================== */
/**
 * スピナーを表示する関数
 */
function ShowSpinner() {
  let Overlay = document.getElementById('SpinnerOverlay')
  if (!Overlay) {
    Overlay = document.createElement('div')
    Overlay.id = 'SpinnerOverlay'
    Overlay.className = 'SpinnerOverlay'
    const Spinner = document.createElement('div')
    Spinner.className = 'Spinner'
    Overlay.appendChild(Spinner)
    document.body.appendChild(Overlay)
  }
  Overlay.style.display = 'flex'
}

/**
 * スピナーを非表示にする関数
 */
function HideSpinner() {
  const Overlay = document.getElementById('SpinnerOverlay')
  if (Overlay) {
    Overlay.style.display = 'none'
  }
}

/* ==========================================================================
 * セッション管理
 * ========================================================================== */
/** セッションタイマーID */
let SessionTimerId = null

/**
 * セッションタイマーをリセットする関数
 */
function ResetSessionTimer() {
  if (SessionTimerId) {
    clearTimeout(SessionTimerId)
  }
  SessionTimerId = setTimeout(() => {
    SessionTimeout()
  }, SESSION_TIMEOUT)
}

/**
 * セッションタイムアウト処理
 */
function SessionTimeout() {
  const CurrentPath = window.location.pathname
  sessionStorage.setItem('LastPath', CurrentPath)
  ShowInfoDialog(
    'セッションタイムアウト',
    '一定時間操作がなかったため、ログアウトしました。',
  )
  setTimeout(() => {
    window.location.href = '/index.html'
  }, 2000)
}

/**
 * プレースホルダー画面を表示
 * @param {string} Title タイトル
 * @param {string} Icon アイコン
 */
function ShowPlaceholder(Title, Icon) {
  const ContentArea = document.getElementById('ContentArea')
  ContentArea.innerHTML = `
    ${CreateContentHeader(Title, Icon)}
      <div style="text-align: center; padding: 50px; color: #999;">
        <i class="fas fa-${Icon}" style="font-size: 60px; margin-bottom: 20px;"></i>
          <p>この機能は準備中です</p>
      </div>
  `
}

/**
 * セッション監視を開始する関数
 */
function StartSessionMonitor() {
  document.addEventListener('click', ResetSessionTimer)
  document.addEventListener('keypress', ResetSessionTimer)
  document.addEventListener('scroll', ResetSessionTimer)
  ResetSessionTimer()
}

/* ==========================================================================
 * ファイルダウンロード
 * ========================================================================== */
/**
 * Blobをダウンロードする共通関数
 * @param {Blob} BlobData バイナリデータ
 * @param {string} Filename 保存名
 */
function DownloadFile(BlobData, Filename) {
  const Url = window.URL.createObjectURL(BlobData)
  const Anchor = document.createElement('a')
  Anchor.href = Url
  Anchor.download = Filename
  Anchor.click()
  window.URL.revokeObjectURL(Url)
}

/* ==========================================================================
 * バリデーション
 * ========================================================================== */
/**
 * ユーザーIDの形式チェック（大文字・記号・数値を含む12桁）
 * @param {string} UserId ユーザーID
 * @returns {boolean} 形式が正しいかどうか
 */
function ValidateUserId(UserId) {
  if (!UserId || UserId.length !== 12) {
    return false
  }
  const HasUpperCase = /[A-Z]/.test(UserId)
  const HasNumber = /[0-9]/.test(UserId)
  const HasSymbol = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(UserId)
  return HasUpperCase && HasNumber && HasSymbol
}

/**
 * 必須入力チェック
 * @param {string} Value チェック対象の値
 * @returns {boolean} 入力されているかどうか
 */
function IsRequired(Value) {
  return Value !== null && Value !== undefined && Value.trim() !== ''
}

/**
 * 桁数チェック
 * @param {string} Value チェック対象の値
 * @param {number} Length 必要な桁数
 * @returns {boolean} 桁数が一致するかどうか
 */
function CheckLength(Value, Length) {
  return Value && Value.length === Length
}

/* ==========================================================================
 * ページネーション
 * ========================================================================== */
/**
 * ページネーションを生成する関数
 * @param {number} TotalRecords 総レコード数
 * @param {number} CurrentPage 現在のページ
 * @param {Function} OnPageChange ページ変更時のコールバック
 * @returns {HTMLElement} ページネーション要素
 */
function CreatePagination(TotalRecords, CurrentPage, OnPageChange) {
  const TotalPages = Math.ceil(TotalRecords / RECORDS_PER_PAGE)
  const Container = document.createElement('div')
  Container.className = 'PaginationContainer'

  if (TotalPages <= 1) {
    return Container
  }

  /** 前へボタン */
  const PrevButton = document.createElement('button')
  PrevButton.className = 'PaginationButton'
  PrevButton.innerHTML = '<i class="fas fa-chevron-left"></i>'
  PrevButton.disabled = CurrentPage === 1
  PrevButton.onclick = () => OnPageChange(CurrentPage - 1)
  Container.appendChild(PrevButton)

  /** ページ番号ボタン */
  const StartPage = Math.max(1, CurrentPage - 2)
  const EndPage = Math.min(TotalPages, CurrentPage + 2)

  for (let i = StartPage; i <= EndPage; i++) {
    const PageButton = document.createElement('button')
    PageButton.className =
      'PaginationButton' + (i === CurrentPage ? ' Active' : '')
    PageButton.textContent = i
    PageButton.onclick = () => OnPageChange(i)
    Container.appendChild(PageButton)
  }

  /** 次へボタン */
  const NextButton = document.createElement('button')
  NextButton.className = 'PaginationButton'
  NextButton.innerHTML = '<i class="fas fa-chevron-right"></i>'
  NextButton.disabled = CurrentPage === TotalPages
  NextButton.onclick = () => OnPageChange(CurrentPage + 1)
  Container.appendChild(NextButton)

  return Container
}

/**
 * レコード件数表示を生成する関数
 * @param {number} TotalRecords 総レコード数
 * @returns {HTMLElement} 件数表示要素
 */
function CreateRecordCount(TotalRecords) {
  const CountDiv = document.createElement('div')
  CountDiv.className = 'RecordCount'
  CountDiv.textContent = `${FormatNumber(TotalRecords)}件`
  return CountDiv
}

/* ==========================================================================
 * DOM要素作成ヘルパー
 * ========================================================================== */
/**
 * 入力フォームを作成する関数
 * @param {Object} Options オプション
 * @returns {HTMLElement} 入力要素
 */
function CreateInput(Options = {}) {
  const Input = document.createElement('input')
  Input.type = Options.type || 'text'
  Input.className = 'InputForm'
  if (Options.id) Input.id = Options.id
  if (Options.placeholder) Input.placeholder = Options.placeholder
  if (Options.maxLength) Input.maxLength = Options.maxLength
  if (Options.disabled) Input.disabled = true
  if (Options.value) Input.value = Options.value
  return Input
}

/**
 * セレクトボックスを作成する関数
 * @param {Object} Options オプション
 * @returns {HTMLElement} セレクト要素
 */
function CreateSelect(Options = {}) {
  const Select = document.createElement('select')
  Select.className = 'SelectForm'
  if (Options.id) Select.id = Options.id
  if (Options.pulldownType) {
    Select.innerHTML = PullDownList(Options.pulldownType)
  }
  if (Options.disabled) Select.disabled = true
  return Select
}

/**
 * ボタンを作成する関数
 * @param {Object} Options オプション
 * @returns {HTMLElement} ボタン要素
 */
function CreateButton(Options = {}) {
  const Button = document.createElement('button')
  Button.className = `ButtonClass ${Options.className || 'PrimaryButton'}`
  if (Options.id) Button.id = Options.id
  if (Options.icon) {
    Button.innerHTML = `<i class="fas fa-${Options.icon}"></i> ${
      Options.text || ''
    }`
  } else {
    Button.textContent = Options.text || ''
  }
  if (Options.onClick) Button.onclick = Options.onClick
  if (Options.disabled) Button.disabled = true
  return Button
}

/* ==========================================================================
 * 利用者区分チェック
 * ========================================================================== */
/**
 * 編集権限があるかチェックする関数
 * @param {boolean} IsCreator 作成者かどうか
 * @param {boolean} IsAdmin 管理者かどうか
 * @returns {boolean} 編集権限があるかどうか
 */
function HasEditPermission(IsCreator, IsAdmin) {
  return IsCreator || IsAdmin
}

/**
 * アシスタント権限があるかチェックする関数（レビュー用）
 * @param {boolean} IsAssistant アシスタントかどうか
 * @param {boolean} IsAdmin 管理者かどうか
 * @returns {boolean} 権限があるかどうか
 */
function HasAssistantPermission(IsAssistant, IsAdmin) {
  return IsAssistant || IsAdmin
}

/* ==========================================================================
 * クリップボード
 * ========================================================================== */
/**
 * テキストをクリップボードにコピーする関数
 * @param {string} Text コピーするテキスト
 * @returns {Promise<boolean>} 成功したかどうか
 */
async function CopyToClipboard(Text) {
  try {
    await navigator.clipboard.writeText(Text)
    return true
  } catch (Error) {
    console.error('Clipboard Error:', Error)
    return false
  }
}

/* ==========================================================================
 * 初期化
 * ========================================================================== */
/**
 * ページ初期化時の共通処理
 */
function InitializePage() {
  /** Font Awesome読み込み確認 */
  if (!document.querySelector('link[href*="font-awesome"]')) {
    const FontAwesome = document.createElement('link')
    FontAwesome.rel = 'stylesheet'
    FontAwesome.href =
      'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
    document.head.appendChild(FontAwesome)
  }
}

function EscapeHtml(str) {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;')
}

/** DOMContentLoaded時に初期化 */
document.addEventListener('DOMContentLoaded', InitializePage)
