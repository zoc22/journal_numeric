import { useState, useEffect, useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useEditor, EditorContent } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import {
  Save, Send, ArrowLeft, Bold, Italic, List, ListOrdered,
  Heading2, Heading3, Quote, Code, Image as ImageIcon, Link as LinkIcon
} from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import { Card } from '../../components/ui/Card'
import { StatusBadge } from '../../components/ui/Badge'
import Spinner from '../../components/ui/Spinner'
import articleService from '../../services/articleService'
import toast from 'react-hot-toast'
import { getErrorMessage } from '../../lib/utils'

function ToolbarButton({ onClick, active, title, children }) {
  return (
    <button
      type="button"
      onClick={onClick}
      title={title}
      className={`p-2 rounded-lg transition-colors ${
        active ? 'bg-brand-50 text-brand' : 'text-ink-500 hover:bg-ink-100 hover:text-ink-900'
      }`}
    >
      {children}
    </button>
  )
}

export default function ArticleEditor() {
  const { id }    = useParams()
  const navigate  = useNavigate()
  const isEdit    = !!id

  const [title,    setTitle]    = useState('')
  const [resume,   setResume]   = useState('')
  const [statut,   setStatut]   = useState('brouillon')
  const [loading,  setLoading]  = useState(isEdit)
  const [saving,   setSaving]   = useState(false)
  const [submitting, setSubmitting] = useState(false)

  const editor = useEditor({
    extensions: [
      StarterKit,
      Image.configure({ inline: false }),
      Link.configure({ openOnClick: false }),
      Placeholder.configure({ placeholder: 'Commencez à rédiger votre article…' }),
    ],
    content: '',
  })

  // Load article if editing
  useEffect(() => {
    if (!isEdit || !editor) return
    articleService.getById(id)
      .then(r => {
        const a = r.data?.data || r.data
        setTitle(a.titre || '')
        setResume(a.resume || '')
        setStatut(a.statut || 'brouillon')
        editor.commands.setContent(a.contenu || '')
      })
      .catch(() => toast.error('Article introuvable'))
      .finally(() => setLoading(false))
  }, [id, editor]) // eslint-disable-line

  const handleSave = async (shouldSubmit = false) => {
    const content = editor?.getHTML() || ''
    if (!title.trim()) { toast.error('Le titre est obligatoire'); return }
    if (!content || content === '<p></p>') { toast.error('Le contenu est obligatoire'); return }

    const setter = shouldSubmit ? setSubmitting : setSaving
    setter(true)

    try {
      const payload = { titre: title, contenu: content, resume }
      let articleId = id

      if (isEdit) {
        await articleService.update(id, payload)
      } else {
        const res = await articleService.create(payload)
        articleId = res.data?.data?.id || res.data?.id
      }

      if (shouldSubmit) {
        await articleService.submit(articleId)
        toast.success('Article soumis pour relecture !')
        navigate('/dashboard')
      } else {
        toast.success('Article sauvegardé !')
        if (!isEdit) navigate(`/editor/${articleId}`, { replace: true })
      }
    } catch (err) {
      toast.error(getErrorMessage(err))
    } finally {
      setter(false)
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Spinner size="xl" />
      </div>
    )
  }

  const canSubmit = statut === 'brouillon' || statut === 'correction_demandee'

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">

          {/* Header bar */}
          <div className="flex items-center justify-between mb-6 gap-4">
            <button
              onClick={() => navigate(-1)}
              className="flex items-center gap-1.5 text-sm text-ink-500 hover:text-ink-900"
            >
              <ArrowLeft className="w-4 h-4" /> Retour
            </button>
            <div className="flex items-center gap-2">
              <StatusBadge status={statut} />
              <Button
                variant="secondary"
                size="sm"
                onClick={() => handleSave(false)}
                isLoading={saving}
                leftIcon={<Save className="w-3.5 h-3.5" />}
              >
                Enregistrer
              </Button>
              {canSubmit && (
                <Button
                  size="sm"
                  onClick={() => handleSave(true)}
                  isLoading={submitting}
                  leftIcon={<Send className="w-3.5 h-3.5" />}
                >
                  Soumettre pour relecture
                </Button>
              )}
            </div>
          </div>

          {/* Editor card */}
          <Card className="overflow-hidden">
            {/* Title */}
            <div className="px-8 pt-8 pb-4 border-b border-ink-100">
              <input
                type="text"
                value={title}
                onChange={e => setTitle(e.target.value)}
                placeholder="Titre de l'article…"
                className="w-full font-display text-3xl sm:text-4xl font-bold text-ink-900 placeholder:text-ink-300 bg-transparent outline-none"
              />
              <input
                type="text"
                value={resume}
                onChange={e => setResume(e.target.value)}
                placeholder="Résumé (optionnel — 1-2 phrases)"
                className="w-full mt-3 text-ink-500 placeholder:text-ink-300 bg-transparent outline-none text-sm"
              />
            </div>

            {/* Toolbar */}
            <div className="flex flex-wrap items-center gap-1 px-8 py-3 border-b border-ink-100 bg-ink-50/50">
              <ToolbarButton onClick={() => editor?.chain().focus().toggleBold().run()} active={editor?.isActive('bold')} title="Gras">
                <Bold className="w-4 h-4" />
              </ToolbarButton>
              <ToolbarButton onClick={() => editor?.chain().focus().toggleItalic().run()} active={editor?.isActive('italic')} title="Italique">
                <Italic className="w-4 h-4" />
              </ToolbarButton>
              <div className="w-px h-5 bg-ink-200 mx-1" />
              <ToolbarButton onClick={() => editor?.chain().focus().toggleHeading({ level: 2 }).run()} active={editor?.isActive('heading', { level: 2 })} title="Titre 2">
                <Heading2 className="w-4 h-4" />
              </ToolbarButton>
              <ToolbarButton onClick={() => editor?.chain().focus().toggleHeading({ level: 3 }).run()} active={editor?.isActive('heading', { level: 3 })} title="Titre 3">
                <Heading3 className="w-4 h-4" />
              </ToolbarButton>
              <div className="w-px h-5 bg-ink-200 mx-1" />
              <ToolbarButton onClick={() => editor?.chain().focus().toggleBulletList().run()} active={editor?.isActive('bulletList')} title="Liste">
                <List className="w-4 h-4" />
              </ToolbarButton>
              <ToolbarButton onClick={() => editor?.chain().focus().toggleOrderedList().run()} active={editor?.isActive('orderedList')} title="Liste numérotée">
                <ListOrdered className="w-4 h-4" />
              </ToolbarButton>
              <div className="w-px h-5 bg-ink-200 mx-1" />
              <ToolbarButton onClick={() => editor?.chain().focus().toggleBlockquote().run()} active={editor?.isActive('blockquote')} title="Citation">
                <Quote className="w-4 h-4" />
              </ToolbarButton>
              <ToolbarButton onClick={() => editor?.chain().focus().toggleCode().run()} active={editor?.isActive('code')} title="Code">
                <Code className="w-4 h-4" />
              </ToolbarButton>
            </div>

            {/* Editor content */}
            <div className="px-8 py-6 min-h-[500px]">
              <EditorContent editor={editor} />
            </div>
          </Card>

        </div>
      </main>
    </div>
  )
}