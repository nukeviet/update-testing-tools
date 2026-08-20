#!/bin/bash

set +o history

SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
  TARGET="$(readlink "$SOURCE")"
  if [[ $TARGET == /* ]]; then
    SOURCE="$TARGET"
  else
    DIR="$(dirname "$SOURCE")"
    SOURCE="$DIR/$TARGET"
  fi
done
DIR="$(cd -P "$(dirname "$SOURCE")" >/dev/null 2>&1 && pwd)"
cd "$DIR"
DIR_PATH=$PWD

if [ ! -f "$DIR_PATH/.env" ]; then
  read -p "Not found .env file in $DIR_PATH"
  exit 1
fi

# Kiểm tra phải có rsync
if ! command -v rsync &>/dev/null; then
  read -p "rsync command not found. Please install rsync."
  exit 1
fi

# Khởi chạy selenium
LOGFILE=$(mktemp)
SELENIUM_CMD="selenium-standalone start"

cleanup() {
  echo "Stopping selenium..."
  if kill -0 $SELENIUM_PID 2>/dev/null; then
    kill $SELENIUM_PID
    wait $SELENIUM_PID
  fi
  rm -f "$LOGFILE"
}
trap cleanup EXIT INT TERM

echo "Starting selenium..."
$SELENIUM_CMD >"$LOGFILE" 2>&1 &
SELENIUM_PID=$!

FOUND=""
for i in {1..10}; do
  sleep 1
  if grep -q "Selenium started" "$LOGFILE"; then
    FOUND="yes"
    break
  fi
done

if [ "$FOUND" != "yes" ]; then
  echo "Selenium failed to start within 10 seconds."
  echo "Log output:"
  echo "----------------------"
  cat "$LOGFILE"
  echo "----------------------"
  read -p "Error! Press any key to continue..."
  exit 1
fi

if ! kill -0 $SELENIUM_PID 2>/dev/null; then
  echo "Selenium exited before"
  wait $SELENIUM_PID
  EXIT_CODE=$?
  if [ $EXIT_CODE -ne 0 ]; then
    read -p "Selenium exited with code: $EXIT_CODE"
    exit $EXIT_CODE
  fi
fi

VERSIONS=(
  "5df659cad72aab26e75cf56d38e638a98c185d30" # 4.3.00
  # "head"                                     # latest
)
VERSIONS_NAME=(
  "4.3.00"
  # "latest"
)
LASTESTNUKEVIETVERSION="nukeviet4.6" # Nhánh chứa bản NukeViet mới nhất để test cập nhật
LASTESTMODULEVERSION="master" # Nhánh chứa bản module mới nhất để test cập nhật
LASTESTUPDATEVERSION="to-4.6.00" # Nhánh update module mới nhất để test cập nhật

NUKEVIETREPOURL="https://github.com/nukeviet/nukeviet.git" # Repo NukeViet để test
MODULEREPOURL="https://github.com/nukeviet/module-videoclips.git" # Repo chứa module để test
UPDATEREPOURL="https://github.com/nukeviet/module-videoclips.git" # Repo chứa gói cập nhật để test

# Chuẩn bị một thư mục làm việc sạch từ một repo git
# prepare_repo <thư mục> <git remote url> <nhánh hoặc commit>
# Nếu thư mục đã tồn tại nhưng không phải repo git hoặc remote url không khớp
# thì xóa thư mục đó đi và clone lại
prepare_repo() {
  local dir="$1"
  local url="$2"
  local ref="$3"
  local current_url=""

  if [ -d "$dir/.git" ]; then
    current_url="$(git -C "$dir" config --get remote.origin.url 2>/dev/null)"
    if [ "$current_url" != "$url" ]; then
      echo "Remote url of $dir is \"$current_url\", expected \"$url\". Removing..."
      rm -rf "$dir"
    fi
  elif [ -d "$dir" ]; then
    echo "$dir is not a git repository. Removing..."
    rm -rf "$dir"
  fi

  if [ ! -d "$dir" ]; then
    echo "Cloning $url into $dir..."
    mkdir -p "$dir"
    git clone "$url" "$dir"
    code=$?
    if [[ $code -gt 0 ]]; then
      echo "Git clone $url failed with code: $code"
      read -p "Error! Press any key to continue..."
      exit $code
    fi
  else
    git -C "$dir" reset --hard HEAD
    git -C "$dir" clean -dfx
    git -C "$dir" fetch --all --prune
  fi

  git -C "$dir" checkout "$ref"
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Git checkout $ref failed with code: $code"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Chỉ pull khi đang ở trên một nhánh, checkout theo commit id sẽ ở trạng thái detached HEAD
  if git -C "$dir" symbolic-ref -q HEAD >/dev/null; then
    git -C "$dir" pull
  fi
}


# Chuẩn bị thư mục src sạch với bản NukeViet mới nhất
prepare_repo "$DIR_PATH/src" "$NUKEVIETREPOURL" "$LASTESTNUKEVIETVERSION"

# Chuẩn bị thư mục src-module sạch với bản module mới nhất
prepare_repo "$DIR_PATH/src-module" "$MODULEREPOURL" "$LASTESTMODULEVERSION"

# Lấy gói cập nhật về thư mục update
prepare_repo "$DIR_PATH/update" "$UPDATEREPOURL" "$LASTESTUPDATEVERSION"

for i in "${!VERSIONS[@]}"; do
  commitid="${VERSIONS[$i]}"
  version_name="${VERSIONS_NAME[$i]}"

  echo "=============================="
  echo "Testing on module version: $version_name"
  echo "=============================="
  echo ""

  # Làm sạch thư mục code NukeViet và checkout về phiên bản LASTESTNUKEVIETVERSION
  cd "$DIR_PATH/src"
  git reset --hard HEAD
  git clean -dfx
  git checkout "$LASTESTNUKEVIETVERSION"
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Git checkout $LASTESTNUKEVIETVERSION failed with code: $code"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Làm sạch thư mục code module và checkout về phiên bản tương ứng
  cd "$DIR_PATH/src-module"
  git reset --hard HEAD
  git clean -dfx
  if [ "$commitid" == "head" ]; then
    commitid="$LASTESTMODULEVERSION"
  fi
  git checkout "$commitid"
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Git checkout $commitid failed with code: $code"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Chép toàn bộ code từ thư mục src-module sang thư mục src ngoại trừ thư mục .git, .gitignore, config.ini
  rsync -av --exclude='.git' --exclude='.gitignore' --exclude='config.ini' "$DIR_PATH/src-module/" "$DIR_PATH/src/"

  # Cài đặt website
  cd "$DIR_PATH"
  echo "Begin installation..."
  php $DIR_PATH/vendor/bin/codecept run -g install
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Tests failed with code: $code on version $version_name"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Cài đặt module
  echo "Begin module installation..."
  php $DIR_PATH/vendor/bin/codecept run -g install-module-videoclips

  # Test cập nhật phiên bản module
  cp -rf "$DIR_PATH/update/install" "$DIR_PATH/src/"
  echo "Begin update testing..."
  php $DIR_PATH/vendor/bin/codecept run -g update
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Tests failed with code: $code on version $version_name"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Kiểm tra lại sau cập nhật
  echo "Begin verify testing..."
  php $DIR_PATH/vendor/bin/codecept run -g verify-videoclips
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Tests failed with code: $code on version $version_name"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  echo "Tests passed on version $version_name"
done

read -p "Finish All steps! Press any key to continue..."
