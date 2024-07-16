function form(init_data_id, exclude = null) {
  return {
    sel: [],
    slots: {},

    async refetch() {
      while (true) {
        await fetch(exclude === null ? './registration-summary.php' : ('./registration-summary.php?exclude=' + exclude))
          .then(resp => resp.json())
          .then(data => {
            this.slots = data;
          }).then(() => new Promise((resolve) => {
            setTimeout(resolve, 1000);
          })).catch((e) => new Promise((resolve) => {
            setTimeout(resolve, 2000);
          }));
      }
    },

    init() {
      const el = document.getElementById(init_data_id);
      this.slots = JSON.parse(el.textContent);

      this.refetch();
    },

    count(s, timeslotId, boothId) {
      return (s[timeslotId] && s[timeslotId][boothId]) || 0;
    },

    format(timeslotId, boothId) {
      let c = this.count(this.slots, timeslotId, boothId);
      return `${c}/${this.slots['_MAX_SLOTS']}`;
    },


    isFull(timeslotId, boothId) {
      return this.count(this.slots, timeslotId, boothId) >= this.slots['_MAX_SLOTS'];
    }
  };
}

function radio(next_target, init) {
  return {
    opt: null,

    init() {
      const timeslotId = this.$root.dataset.radioTimeslot;
      setTimeout(() => this.opt = init, 10);
      this.$watch('slots', (s) => {
        if (this.count(s, timeslotId, this.opt) >= s['_MAX_SLOTS']) {
          setTimeout(() => this.opt = null, 0);
        }
      })
    },

    input: {
      ['@change']() {
        document.getElementById(next_target).scrollIntoView({
          behavior: 'smooth'
        });
      },
      [':disabled']() {
        if (this.$el.dataset.radioDisabled) return true;
        const timeslotId = this.$root.dataset.radioTimeslot;
        const boothId = this.$el.dataset.radioBooth;

        return (this.opt != this.$el.value && this.sel.includes(this.$el.value)) || this.isFull(timeslotId, boothId);
      }
    },
  };
}

/**
  * @param init string
  */
function description(init = '') {
  return {
    selected: new Set(init.split(',').map(x => x.trim).filter(x => x.length > 0)),
    options: ['student', 'job seeker', 'out of school youth', 'fresh graduate'],
    others: '',

    init() {
      const options = new Set(this.options);
      const other = new Set(this.selected).difference(options);
      this.others = Array.from(other).join(', ');
    },

    get value() {
      return Array.from(this.selected).map(x => x.toLowerCase()).join(',');
    },

    input: {
      ['@change']() {
        const value = this.$el.dataset.descValue;
        if (this.$el.checked) {
          if (!this.selected.has(value)) this.selected.add(value);
        } else {
          this.selected.delete(value);
        }
      },
    },
  };
}

document.addEventListener('alpine:init', () => {
  Alpine.data('radio', radio);
  Alpine.data('form', form);
  Alpine.data('description', description);
})

