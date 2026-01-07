/**
 * Dialog.js
 * ダイアログ関連の共通処理
 * エラー・インフォ・コンファーム・入力エリア・画像・デートピッカーダイアログを定義
 */

/* ==========================================================================
 * エラーダイアログ
 * ========================================================================== */
/**
 * エラーダイアログを表示する関数
 * @param {string} Message エラーメッセージ
 */
function ShowErrorDialog(Message) {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogSmall";

    const Title = document.createElement("div");
    Title.className = "DialogTitle";
    Title.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i> エラー';

    const Content = document.createElement("div");
    Content.className = "DialogContent";
    Content.style.textAlign = "center";
    Content.textContent = Message;

    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";
    ButtonArea.style.justifyContent = "center";

    const CloseButton = document.createElement("button");
    CloseButton.className = "ButtonClass CloseButton";
    CloseButton.innerHTML = '<i class="fas fa-times"></i> 閉じる';
    CloseButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve();
    };

    ButtonArea.appendChild(CloseButton);
    Dialog.appendChild(Title);
    Dialog.appendChild(Content);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);

    /** ESCキーで閉じる */
    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve();
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}

/* ==========================================================================
 * インフォダイアログ
 * ========================================================================== */
/**
 * インフォダイアログを表示する関数
 * @param {string} TitleText タイトル
 * @param {string} Message メッセージ
 */
function ShowInfoDialog(TitleText, Message) {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogSmall";

    const Title = document.createElement("div");
    Title.className = "DialogTitle";
    Title.innerHTML = `<i class="fas fa-info-circle" style="color: #3498db;"></i> ${TitleText}`;

    const Content = document.createElement("div");
    Content.className = "DialogContent";
    Content.style.textAlign = "center";
    Content.textContent = Message;

    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";
    ButtonArea.style.justifyContent = "center";

    const CloseButton = document.createElement("button");
    CloseButton.className = "ButtonClass PrimaryButton";
    CloseButton.innerHTML = '<i class="fas fa-check"></i> OK';
    CloseButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve();
    };

    ButtonArea.appendChild(CloseButton);
    Dialog.appendChild(Title);
    Dialog.appendChild(Content);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);

    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve();
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}

/* ==========================================================================
 * コンファームダイアログ
 * ========================================================================== */
/**
 * コンファームダイアログを表示する関数
 * @param {string} Message 確認メッセージ
 * @returns {Promise<boolean>} はいが選択されたかどうか
 */
function ShowConfirmDialog(Message) {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogSmall";

    const Title = document.createElement("div");
    Title.className = "DialogTitle";
    Title.innerHTML = '<i class="fas fa-question-circle" style="color: #f39c12;"></i> 確認';

    const Content = document.createElement("div");
    Content.className = "DialogContent";
    Content.style.textAlign = "center";
    Content.textContent = Message;

    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";
    ButtonArea.style.justifyContent = "center";

    const NoButton = document.createElement("button");
    NoButton.className = "ButtonClass SecondaryButton";
    NoButton.innerHTML = '<i class="fas fa-times"></i> いいえ';
    NoButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve(false);
    };

    const YesButton = document.createElement("button");
    YesButton.className = "ButtonClass PrimaryButton";
    YesButton.innerHTML = '<i class="fas fa-check"></i> はい';
    YesButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve(true);
    };

    ButtonArea.appendChild(NoButton);
    ButtonArea.appendChild(YesButton);
    Dialog.appendChild(Title);
    Dialog.appendChild(Content);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);

    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve(false);
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}

/* ==========================================================================
 * 入力エリアダイアログ
 * ========================================================================== */
/**
 * 入力エリアダイアログを表示する関数
 * @param {string} CurrentValue 現在の値
 * @param {boolean} ReadOnly 読み取り専用かどうか
 * @returns {Promise<string|null>} 入力された値（キャンセル時はnull）
 */
function ShowInputAreaDialog(CurrentValue = "", ReadOnly = false) {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogMedium";

    const Title = document.createElement("div");
    Title.className = "DialogTitle";
    Title.textContent = "詳細";

    const Content = document.createElement("div");
    Content.className = "DialogContent";

    const TextArea = document.createElement("textarea");
    TextArea.className = "TextAreaForm";
    TextArea.style.minHeight = "200px";
    TextArea.value = CurrentValue;
    TextArea.readOnly = ReadOnly;
    TextArea.placeholder = "内容を入力してください";

    Content.appendChild(TextArea);

    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";

    const CloseButton = document.createElement("button");
    CloseButton.className = "ButtonClass CloseButton";
    CloseButton.innerHTML = '<i class="fas fa-times"></i> 閉じる';
    CloseButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve(null);
    };

    ButtonArea.appendChild(CloseButton);

    if (!ReadOnly) {
      const ClearButton = document.createElement("button");
      ClearButton.className = "ButtonClass ClearButton";
      ClearButton.innerHTML = '<i class="fas fa-sync"></i> クリア';
      ClearButton.onclick = () => {
        TextArea.value = "";
        TextArea.focus();
      };

      const SaveButton = document.createElement("button");
      SaveButton.className = "ButtonClass SaveButton";
      SaveButton.innerHTML = '<i class="fas fa-save"></i> 完了';
      SaveButton.onclick = () => {
        document.body.removeChild(Overlay);
        Resolve(TextArea.value);
      };

      ButtonArea.appendChild(ClearButton);
      ButtonArea.appendChild(SaveButton);
    }

    Dialog.appendChild(Title);
    Dialog.appendChild(Content);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);
    TextArea.focus();

    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve(null);
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}

/* ==========================================================================
 * 画像ダイアログ
 * ========================================================================== */
/**
 * 画像ダイアログを表示する関数
 * @param {string} ImageSrc 画像パス
 * @param {boolean} CanDelete 削除可能かどうか
 * @returns {Promise<boolean>} 削除が選択されたかどうか
 */
function ShowImageDialog(ImageSrc, CanDelete = false) {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogLarge";
    Dialog.style.textAlign = "center";

    const ImageContainer = document.createElement("div");
    ImageContainer.style.marginBottom = "20px";

    const Image = document.createElement("img");
    Image.src = ImageSrc;
    Image.style.maxWidth = "100%";
    Image.style.maxHeight = "60vh";
    Image.style.objectFit = "contain";

    ImageContainer.appendChild(Image);

    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";
    ButtonArea.style.justifyContent = "center";

    const CloseButton = document.createElement("button");
    CloseButton.className = "ButtonClass CloseButton";
    CloseButton.innerHTML = '<i class="fas fa-times"></i> 閉じる';
    CloseButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve(false);
    };

    ButtonArea.appendChild(CloseButton);

    if (CanDelete) {
      const DeleteButton = document.createElement("button");
      DeleteButton.className = "ButtonClass DeleteButton";
      DeleteButton.innerHTML = '<i class="fas fa-trash"></i> 削除';
      DeleteButton.onclick = () => {
        document.body.removeChild(Overlay);
        Resolve(true);
      };
      ButtonArea.appendChild(DeleteButton);
    }

    Dialog.appendChild(ImageContainer);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);

    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve(false);
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}

/* ==========================================================================
 * デートピッカーダイアログ
 * ========================================================================== */
/**
 * デートピッカーダイアログクラス
 */
class DatePicker {
  constructor() {}

  /**
   * 指定IDの input に日付を設定するダイアログを表示
   * @param {string} ElementId 入力値反映先の要素ID
   * @returns {Promise<string|undefined>} フォーマットされた日付
   */
  OpenDialog(ElementId) {
    return new Promise((Resolve) => {
      const Target = document.getElementById(ElementId);
      const CurrentVal = (Target?.value || "").trim();

      /** ダイアログ背景 */
      const Overlay = document.createElement("div");
      Overlay.className = "DialogOverlay";

      /** ダイアログ本体 */
      const Dialog = document.createElement("div");
      Dialog.className = "DialogContainer DialogSmall";

      const Title = document.createElement("div");
      Title.className = "DialogTitle";
      Title.textContent = "日付選択";

      const Content = document.createElement("div");
      Content.className = "DialogContent";

      /** 入力モード選択 */
      const ModeSelect = document.createElement("select");
      ModeSelect.className = "SelectForm";
      ModeSelect.style.marginBottom = "15px";
      ModeSelect.innerHTML = `
        <option value="year">年</option>
        <option value="year-month">年月</option>
        <option value="date">年月日</option>
      `;

      /** 入力欄 */
      const Input = document.createElement("input");
      Input.className = "InputForm";
      Input.value = CurrentVal.replace(/[^0-9]/g, "");
      Input.inputMode = "numeric";

      /** モード自動判定 */
      const DetectModeFromValue = (Val) => {
        const Num = Val.replace(/[^0-9]/g, "");
        if (Num.length === 8) return "date";
        if (Num.length === 6) return "year-month";
        if (Num.length === 4) return "year";
        return "year";
      };

      const UpdatePlaceholder = () => {
        if (ModeSelect.value === "year") {
          Input.placeholder = "例: 2025";
          Input.maxLength = 4;
        } else if (ModeSelect.value === "year-month") {
          Input.placeholder = "例: 202507";
          Input.maxLength = 6;
        } else {
          Input.placeholder = "例: 20250724";
          Input.maxLength = 8;
        }
        Input.focus();
      };

      /** 初期モード設定 */
      if (CurrentVal) {
        ModeSelect.value = DetectModeFromValue(CurrentVal);
      } else {
        ModeSelect.value = "year";
      }
      UpdatePlaceholder();

      ModeSelect.onchange = () => {
        Input.value = "";
        UpdatePlaceholder();
      };

      /** 入力制御 */
      Input.addEventListener("input", (E) => {
        E.target.value = E.target.value.replace(/[^0-9]/g, "");
        const MaxLen = parseInt(Input.maxLength, 10);
        if (E.target.value.length > MaxLen) {
          E.target.value = E.target.value.slice(0, MaxLen);
        }
      });

      Content.appendChild(ModeSelect);
      Content.appendChild(Input);

      /** ボタン類 */
      const ButtonArea = document.createElement("div");
      ButtonArea.className = "DialogButtonArea";

      const CloseButton = document.createElement("button");
      CloseButton.className = "ButtonClass CloseButton";
      CloseButton.innerHTML = '<i class="fas fa-times"></i> 閉じる';

      const ClearButton = document.createElement("button");
      ClearButton.className = "ButtonClass ClearButton";
      ClearButton.innerHTML = '<i class="fas fa-sync"></i> クリア';

      const OkButton = document.createElement("button");
      OkButton.className = "ButtonClass SaveButton";
      OkButton.innerHTML = '<i class="fas fa-save"></i> 決定';

      ButtonArea.appendChild(CloseButton);
      ButtonArea.appendChild(ClearButton);
      ButtonArea.appendChild(OkButton);

      Dialog.appendChild(Title);
      Dialog.appendChild(Content);
      Dialog.appendChild(ButtonArea);
      Overlay.appendChild(Dialog);
      document.body.appendChild(Overlay);
      Input.focus();

      /** 閉じ処理 */
      const Close = (ReturnValue = undefined) => {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve(ReturnValue);
      };

      const EscHandler = (E) => {
        if (E.key === "Escape") Close();
      };
      document.addEventListener("keydown", EscHandler);

      CloseButton.onclick = () => Close();
      ClearButton.onclick = () => {
        Input.value = "";
        Input.focus();
      };
      OkButton.onclick = () => {
        const Formatted = this.FormatValue(Input.value, ModeSelect.value);
        if (Target) Target.value = Formatted || "";
        Close(Formatted);
      };
    });
  }

  /**
   * 値整形（ゼロ埋め対応）
   * @param {string} Value 入力値
   * @param {string} Mode モード
   * @returns {string} フォーマットされた値
   */
  FormatValue(Value, Mode) {
    Value = Value.trim();
    if (!Value) return "";

    if (Mode === "year") {
      return Value.length === 4 ? Value : "";
    }

    if (Mode === "year-month") {
      const Y = Value.slice(0, 4);
      let M = Value.slice(4, 6);
      if (M.length === 1) M = "0" + M;
      return M ? `${Y}-${M}` : "";
    }

    if (Mode === "date") {
      const Y = Value.slice(0, 4);
      let M = Value.slice(4, 6);
      let D = Value.slice(6, 8);
      if (M.length === 1) M = "0" + M;
      if (D.length === 1) D = "0" + D;
      return Y && M && D ? `${Y}-${M}-${D}` : "";
    }

    return "";
  }
}

/** グローバル登録 */
window.DatePicker = DatePicker;

/* ==========================================================================
 * 作品作成ダイアログ
 * ========================================================================== */
/**
 * 作品作成ダイアログを表示する関数
 * @returns {Promise<Object|null>} 作品情報（キャンセル時はnull）
 */
function ShowWorkCreateDialog() {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogSmall";

    const Title = document.createElement("div");
    Title.className = "DialogTitle";
    Title.textContent = "作品作成";

    const Content = document.createElement("div");
    Content.className = "DialogContent";

    /** 作品名入力 */
    const WorkNameGroup = document.createElement("div");
    WorkNameGroup.className = "FormGroup";
    const WorkNameInput = document.createElement("input");
    WorkNameInput.className = "InputForm";
    WorkNameInput.id = "WorkNameInput";
    WorkNameInput.placeholder = "作品名（50文字以内）";
    WorkNameInput.maxLength = 50;
    WorkNameGroup.appendChild(WorkNameInput);

    /** ジャンル選択 */
    const GenreGroup = document.createElement("div");
    GenreGroup.className = "FormGroup";
    const GenreSelect = document.createElement("select");
    GenreSelect.className = "SelectForm";
    GenreSelect.id = "GenreSelect";
    GenreSelect.innerHTML = PullDownList("Genre");
    GenreGroup.appendChild(GenreSelect);

    Content.appendChild(WorkNameGroup);
    Content.appendChild(GenreGroup);

    /** ボタン */
    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";

    const CloseButton = document.createElement("button");
    CloseButton.className = "ButtonClass CloseButton";
    CloseButton.innerHTML = '<i class="fas fa-times"></i> 閉じる';
    CloseButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve(null);
    };

    const CreateButton = document.createElement("button");
    CreateButton.className = "ButtonClass SaveButton";
    CreateButton.innerHTML = '<i class="fas fa-plus"></i> 作成';
    CreateButton.onclick = async () => {
      const WorkName = WorkNameInput.value.trim();
      if (!WorkName) {
        await ShowErrorDialog("作品名を入力してください");
        WorkNameInput.focus();
        return;
      }
      document.body.removeChild(Overlay);
      Resolve({
        WorkTitle: WorkName,
        Genre: parseInt(GenreSelect.value)
      });
    };

    ButtonArea.appendChild(CloseButton);
    ButtonArea.appendChild(CreateButton);

    Dialog.appendChild(Title);
    Dialog.appendChild(Content);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);
    WorkNameInput.focus();

    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve(null);
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}

/* ==========================================================================
 * プロジェクト参加ダイアログ
 * ========================================================================== */
/**
 * プロジェクト参加ダイアログを表示する関数
 * @returns {Promise<string|null>} 作品ID（キャンセル時はnull）
 */
function ShowProjectJoinDialog() {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogSmall";

    const Title = document.createElement("div");
    Title.className = "DialogTitle";
    Title.textContent = "プロジェクト参加";

    const Content = document.createElement("div");
    Content.className = "DialogContent";

    /** 作品ID入力 */
    const InputGroup = document.createElement("div");
    InputGroup.className = "InputWithButton";

    const WorkIdInput = document.createElement("input");
    WorkIdInput.className = "InputForm";
    WorkIdInput.id = "ProjectWorkIdInput";
    WorkIdInput.placeholder = "作品IDを入力";
    WorkIdInput.disabled = true;

    /** Hidden用 */
    const WorkIdHidden = document.createElement("input");
    WorkIdHidden.type = "hidden";
    WorkIdHidden.id = "ProjectWorkIdHidden";

    const DetailButton = document.createElement("button");
    DetailButton.className = "ButtonClass SecondaryButton ButtonSmall";
    DetailButton.innerHTML = '<i class="fas fa-edit"></i>';
    DetailButton.onclick = async () => {
      const Result = await ShowInputAreaDialog(WorkIdHidden.value, false);
      if (Result !== null) {
        WorkIdHidden.value = Result;
        WorkIdInput.value = TruncateText(Result, 30);
      }
    };

    InputGroup.appendChild(WorkIdInput);
    InputGroup.appendChild(DetailButton);

    Content.appendChild(InputGroup);
    Content.appendChild(WorkIdHidden);

    /** ボタン */
    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";

    const CloseButton = document.createElement("button");
    CloseButton.className = "ButtonClass CloseButton";
    CloseButton.innerHTML = '<i class="fas fa-times"></i> 閉じる';
    CloseButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve(null);
    };

    const JoinButton = document.createElement("button");
    JoinButton.className = "ButtonClass SuccessButton";
    JoinButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> 参加';
    JoinButton.onclick = async () => {
      const WorkId = WorkIdHidden.value.trim();
      if (!WorkId) {
        await ShowErrorDialog("作品IDを入力してください");
        return;
      }
      document.body.removeChild(Overlay);
      Resolve(WorkId);
    };

    ButtonArea.appendChild(CloseButton);
    ButtonArea.appendChild(JoinButton);

    Dialog.appendChild(Title);
    Dialog.appendChild(Content);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);

    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve(null);
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}

/* ==========================================================================
 * フィードバック投稿ダイアログ
 * ========================================================================== */
/**
 * フィードバック投稿ダイアログを表示する関数
 * @returns {Promise<Object|null>} フィードバック情報（キャンセル時はnull）
 */
function ShowFeedbackDialog() {
  return new Promise((Resolve) => {
    const Overlay = document.createElement("div");
    Overlay.className = "DialogOverlay";

    const Dialog = document.createElement("div");
    Dialog.className = "DialogContainer DialogMedium";

    const Title = document.createElement("div");
    Title.className = "DialogTitle";
    Title.textContent = "フィードバック";

    const Content = document.createElement("div");
    Content.className = "DialogContent";

    /** タイトル入力 */
    const TitleGroup = document.createElement("div");
    TitleGroup.className = "FormGroup";
    const TitleInput = document.createElement("input");
    TitleInput.className = "InputForm";
    TitleInput.id = "FeedbackTitleInput";
    TitleInput.placeholder = "タイトル（100文字以内）";
    TitleInput.maxLength = 100;
    TitleGroup.appendChild(TitleInput);

    /** 内容入力 */
    const ContentGroup = document.createElement("div");
    ContentGroup.className = "FormGroup";
    const ContentInputGroup = document.createElement("div");
    ContentInputGroup.className = "InputWithButton";

    const ContentInput = document.createElement("input");
    ContentInput.className = "InputForm";
    ContentInput.id = "FeedbackContentInput";
    ContentInput.placeholder = "内容";
    ContentInput.disabled = true;

    const ContentHidden = document.createElement("input");
    ContentHidden.type = "hidden";
    ContentHidden.id = "FeedbackContentHidden";

    const DetailButton = document.createElement("button");
    DetailButton.className = "ButtonClass SecondaryButton ButtonSmall";
    DetailButton.innerHTML = '<i class="fas fa-edit"></i>';
    DetailButton.onclick = async () => {
      const Result = await ShowInputAreaDialog(ContentHidden.value, false);
      if (Result !== null) {
        ContentHidden.value = Result;
        ContentInput.value = TruncateText(Result, 30);
      }
    };

    ContentInputGroup.appendChild(ContentInput);
    ContentInputGroup.appendChild(DetailButton);
    ContentGroup.appendChild(ContentInputGroup);

    Content.appendChild(TitleGroup);
    Content.appendChild(ContentGroup);
    Content.appendChild(ContentHidden);

    /** ボタン */
    const ButtonArea = document.createElement("div");
    ButtonArea.className = "DialogButtonArea";

    const CloseButton = document.createElement("button");
    CloseButton.className = "ButtonClass CloseButton";
    CloseButton.innerHTML = '<i class="fas fa-times"></i> 閉じる';
    CloseButton.onclick = () => {
      document.body.removeChild(Overlay);
      Resolve(null);
    };

    const PostButton = document.createElement("button");
    PostButton.className = "ButtonClass SuccessButton";
    PostButton.innerHTML = '<i class="fas fa-paper-plane"></i> 投稿';
    PostButton.onclick = async () => {
      const FeedbackTitle = TitleInput.value.trim();
      const FeedbackContent = ContentHidden.value.trim();

      if (!FeedbackTitle) {
        await ShowErrorDialog("タイトルを入力してください");
        TitleInput.focus();
        return;
      }
      if (!FeedbackContent) {
        await ShowErrorDialog("内容を入力してください");
        return;
      }

      document.body.removeChild(Overlay);
      Resolve({
        Title: FeedbackTitle,
        Content: FeedbackContent
      });
    };

    ButtonArea.appendChild(CloseButton);
    ButtonArea.appendChild(PostButton);

    Dialog.appendChild(Title);
    Dialog.appendChild(Content);
    Dialog.appendChild(ButtonArea);
    Overlay.appendChild(Dialog);
    document.body.appendChild(Overlay);
    TitleInput.focus();

    const EscHandler = (E) => {
      if (E.key === "Escape") {
        document.removeEventListener("keydown", EscHandler);
        document.body.removeChild(Overlay);
        Resolve(null);
      }
    };
    document.addEventListener("keydown", EscHandler);
  });
}
